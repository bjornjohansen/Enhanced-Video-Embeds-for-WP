(function($) {
	$(document).on('ready', function() {
		$('.EVE_Link').on('click', function(ev) {
			ev.preventDefault();
			var $this = $(this);
			$this.replaceWith( $this.attr('data-oembed-html').toString() );
		});
	});
})(jQuery);