
(function ($) {
	$().ready(function () {
		// okay, lets see what's goin' on and hide fields that aren't needed just yet
		function hideDateFields() {
			$('select.day, input[name=Repeat], input[name=SendDifference]').parents('.fieldgroupField').hide();

		}

		function hideChangeFields() {
			$('select.day, input[name=Repeat], input[name=SendDifference]').parents('.fieldgroupField').show();
		}

		var selector = $('select[name=TriggerOn]');

		function checkSelection() {
			if (selector.val() == 'Date') {
				$('select.day, select[name=Repeat], select[name=SendDifference]').parents('.fieldgroupField').show();
			} else {
				$('select.day, select[name=Repeat], select[name=SendDifference]').parents('.fieldgroupField').hide();
			}
		}

		selector.change(checkSelection);
		checkSelection();
	});
})(jQuery);