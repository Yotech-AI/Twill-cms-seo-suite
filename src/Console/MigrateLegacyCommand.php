<?php

namespace TwillSeo\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;
use TwillSeo\Services\ModelRegistry;

/**
 * One-shot adoption helper for sites that carried their own hand-rolled SEO
 * fields before this package: copies legacy per-locale seo_title /
 * description columns from each registered model's translation table into
 * the suite's storage, and optionally clones a legacy share-image media
 * role onto the suite's own role.
 *
 * Idempotent by design: suite values that already exist are never
 * overwritten, and media rows are only cloned for models that have no
 * suite-role media yet — running it twice (or after editors have started
 * filling fields in the CMS) changes nothing it should not.
 */
class MigrateLegacyCommand extends Command
{
    /** Kept in sync with HasSeo::OG_IMAGE_ROLE / SeoFields — see their comments. */
    private const OG_IMAGE_ROLE = 'twill_seo_og_image';

    protected $signature = 'twill-seo:migrate-legacy
        {--type=* : Registry keys to migrate (default: every registered type)}
        {--title-column=seo_title : Legacy translation-table column holding the SEO title}
        {--description-column=description : Legacy translation-table column holding the meta description}
        {--media-role= : Legacy medias role to clone onto the suite share-image role}
        {--dry-run : Report what would change without writing anything}';

    protected $description = 'Copy legacy hand-rolled SEO fields (title, description, share image) into the Twill SEO suite';

    public function handle(ModelRegistry $registry): int
    {
        $types = $this->option('type') !== []
            ? $this->option('type')
            : array_keys($registry->all());

        if ($types === []) {
            $this->warn('No models are registered in twill-seo.models — nothing to migrate.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $rows = [];

        foreach ($types as $type) {
            if (! $registry->has($type)) {
                $this->error("Unknown registry key \"{$type}\" — check twill-seo.models.");

                return self::FAILURE;
            }

            $rows[] = $this->migrateType($registry, $type, $dryRun);
        }

        $this->table(
            ['Type', 'Entries created', 'Titles copied', 'Descriptions copied', 'Media cloned', 'Note'],
            $rows
        );

        if ($dryRun) {
            $this->info('Dry run: nothing was written.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0:string,1:int,2:int,3:int,4:int,5:string}
     */
    private function migrateType(ModelRegistry $registry, string $type, bool $dryRun): array
    {
        $modelClass = $registry->modelClass($type);
        $model = new $modelClass;

        if (! method_exists($model, 'translations')) {
            return [$type, 0, 0, 0, 0, 'skipped: model has no translations relation'];
        }

        $relation = $model->translations();
        $table = $relation->getRelated()->getTable();
        $foreignKey = $relation->getForeignKeyName();

        $titleColumn = (string) $this->option('title-column');
        $descriptionColumn = (string) $this->option('description-column');

        $hasTitle = Schema::hasColumn($table, $titleColumn);
        $hasDescription = Schema::hasColumn($table, $descriptionColumn);

        if (! $hasTitle && ! $hasDescription) {
            return [$type, 0, 0, 0, 0, "skipped: {$table} has neither \"{$titleColumn}\" nor \"{$descriptionColumn}\""];
        }

        $columns = array_merge(
            [$foreignKey, 'locale'],
            $hasTitle ? [$titleColumn] : [],
            $hasDescription ? [$descriptionColumn] : [],
        );

        $legacyRows = DB::table($table)
            ->select($columns)
            ->where(function ($query) use ($hasTitle, $hasDescription, $titleColumn, $descriptionColumn): void {
                if ($hasTitle) {
                    $query->orWhereNotNull($titleColumn);
                }
                if ($hasDescription) {
                    $query->orWhereNotNull($descriptionColumn);
                }
            })
            ->get();

        $created = $titles = $descriptions = 0;
        $morphClass = $model->getMorphClass();

        foreach ($legacyRows as $row) {
            $seoTitle = $hasTitle ? $this->trimToNull($row->{$titleColumn}) : null;
            $seoDescription = $hasDescription ? $this->trimToNull($row->{$descriptionColumn}) : null;

            if ($seoTitle === null && $seoDescription === null) {
                continue;
            }

            [$didCreate, $didTitle, $didDescription] = $this->upsertSuiteTranslation(
                $morphClass,
                (int) $row->{$foreignKey},
                (string) $row->locale,
                $seoTitle,
                $seoDescription,
                $dryRun,
            );

            $created += $didCreate;
            $titles += $didTitle;
            $descriptions += $didDescription;
        }

        $media = 0;
        $note = '';

        if (($legacyRole = $this->option('media-role')) !== null && $legacyRole !== '') {
            try {
                $media = $this->cloneMediaRole($morphClass, (string) $legacyRole, $dryRun);
            } catch (Throwable $e) {
                report($e);
                $note = 'media clone failed: '.$e->getMessage();
            }
        }

        return [$type, $created, $titles, $descriptions, $media, $note];
    }

    /**
     * @return array{0:int,1:int,2:int} [entry created, title copied, description copied]
     */
    private function upsertSuiteTranslation(
        string $morphClass,
        int $modelId,
        string $locale,
        ?string $seoTitle,
        ?string $seoDescription,
        bool $dryRun,
    ): array {
        $entryId = DB::table('twill_seo_entries')->where([
            'seoable_type' => $morphClass,
            'seoable_id' => $modelId,
        ])->value('id');

        $createdEntry = 0;

        if ($entryId === null) {
            if (! $dryRun) {
                $entryId = DB::table('twill_seo_entries')->insertGetId([
                    'seoable_type' => $morphClass,
                    'seoable_id' => $modelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $createdEntry = 1;
        }

        $translation = $entryId !== null
            ? DB::table('twill_seo_entry_translations')->where([
                'twill_seo_entry_id' => $entryId,
                'locale' => $locale,
            ])->first()
            : null;

        $copiedTitle = (int) ($seoTitle !== null && ($translation === null || $translation->seo_title === null));
        $copiedDescription = (int) ($seoDescription !== null && ($translation === null || $translation->seo_description === null));

        if ($dryRun || ($copiedTitle === 0 && $copiedDescription === 0)) {
            return [$createdEntry, $copiedTitle, $copiedDescription];
        }

        if ($translation === null) {
            DB::table('twill_seo_entry_translations')->insert([
                'twill_seo_entry_id' => $entryId,
                'locale' => $locale,
                'seo_title' => $seoTitle,
                'seo_description' => $seoDescription,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [$createdEntry, $copiedTitle, $copiedDescription];
        }

        $updates = ['updated_at' => now()];

        if ($copiedTitle === 1) {
            $updates['seo_title'] = $seoTitle;
        }

        if ($copiedDescription === 1) {
            $updates['seo_description'] = $seoDescription;
        }

        DB::table('twill_seo_entry_translations')->where('id', $translation->id)->update($updates);

        return [$createdEntry, $copiedTitle, $copiedDescription];
    }

    /**
     * Clones every mediables row of the legacy role onto the suite role, per
     * model, skipping models that already carry suite-role media. Rows are
     * cloned column-for-column (minus the primary key) so crop data and
     * per-locale attachments survive whatever Twill version shaped the table.
     */
    private function cloneMediaRole(string $morphClass, string $legacyRole, bool $dryRun): int
    {
        // Older Twill versions shaped this table differently; only filter on
        // soft deletes when the column is actually there.
        $hasSoftDeletes = Schema::hasColumn('mediables', 'deleted_at');

        $legacyRows = DB::table('mediables')
            ->where('mediable_type', $morphClass)
            ->where('role', $legacyRole)
            ->when($hasSoftDeletes, fn ($query) => $query->whereNull('deleted_at'))
            ->get();

        $cloned = 0;

        foreach ($legacyRows->groupBy('mediable_id') as $mediableId => $rows) {
            $alreadyHasSuiteRole = DB::table('mediables')
                ->where('mediable_type', $morphClass)
                ->where('mediable_id', $mediableId)
                ->where('role', self::OG_IMAGE_ROLE)
                ->when($hasSoftDeletes, fn ($query) => $query->whereNull('deleted_at'))
                ->exists();

            if ($alreadyHasSuiteRole) {
                continue;
            }

            foreach ($rows as $row) {
                if (! $dryRun) {
                    $clone = (array) $row;
                    unset($clone['id']);
                    $clone['role'] = self::OG_IMAGE_ROLE;
                    $clone['created_at'] = now();
                    $clone['updated_at'] = now();

                    DB::table('mediables')->insert($clone);
                }

                $cloned++;
            }
        }

        return $cloned;
    }

    private function trimToNull(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
