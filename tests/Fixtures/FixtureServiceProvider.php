<?php

namespace TwillSeo\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

/**
 * Stands up the fixture CMS for the test harness (currently just the Article
 * module's migrations, loaded directly by TestCase::defineDatabaseMigrations).
 *
 * Empty for now — no fixture blocks exist yet, so there is nothing to
 * register. A later task that tests renderBlocks() will register fixture
 * blocks and their view namespace here, the way the twill-cms-ai-assistent
 * sibling's fixture provider does. Kept as its own provider from the start so
 * that wiring lands without reshuffling TestCase::getPackageProviders().
 */
class FixtureServiceProvider extends ServiceProvider {}
