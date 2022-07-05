jQuery(function ($) {
	function refreshPalette() {
		$(".wrd-setting-palette__input[data-palette]").each(function () {
			let palette = $(this).attr("data-palette");
			let variable = $(this).attr("data-palette-color");
			let color = $(this).val();
			let els = $(".wrd-setting-uses-palette[data-palette=" + palette + "]");

			els.each(function () {
				$(this)[0].style.setProperty("--" + variable, color);
			});
		});
	}
	$(".wrd-setting-palette__input[data-palette]").on(
		"input change",
		refreshPalette
	);
	refreshPalette();
});