jQuery(document).ready(function($) {
    // Target blog and category archive posts
    $('body.archive article.type-post, body.blog article.type-post').each(function() {
        var $this = $(this);
        var $thumbnail = $this.find('.post-thumbnail');
        var $textElements = $this.children().not('.post-thumbnail');
        
        // Wrap text elements (title/header, meta, excerpt) in a div
        $textElements.wrapAll('<div class="sb-post-text"></div>');
        
        // If no thumbnail, make text full width (handled in CSS too)
        if ($thumbnail.length === 0) {
            $this.addClass('no-thumbnail');
        }
    });
})