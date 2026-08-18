{{-- $block is the component's public property (A17\Twill\Models\Block), exposed
     to this view via Illuminate\View\Component::data(). Rendered verbatim: the
     analysis engine is what needs to see paragraphs, not this view. --}}
{!! $block->translatedInput('text') !!}
