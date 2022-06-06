jQuery(function ($) {
	$(".wrd-setting__toggle").on("click keypress", function (e) {
		if (e.key == "Enter" || e.key == " " || e.type == "click") {
			$(e.target).closest(".wrd-setting").toggleClass("wrd-setting-open");
		}
	});

	$("[data-wp-setting-condition]").each(function () {
		let target = $(this);
		let track_id = target.attr("data-wp-setting-condition");
		let $track = $(track_id);

		$track.on("change", function (e) {
			let $inp = $(e.target);

			if (!$inp.is(":checked")) {
				target.addClass("wp-setting-disabled");
			} else {
				target.removeClass("wp-setting-disabled");
			}
		});
	});
});
