jQuery(function ($) {
	function change_fonts_page($setting, pageChange) {
		let $search = $setting.find(".wrd-setting-font__search");
		let search = $search.val();
		let page = parseInt($setting.attr("data-page")) + pageChange;

		if (page < 1) {
			page = 1;
		}

		search_fonts($setting, search, page);
	}

	function search_fonts($setting, search, page) {
		let perPage = 25;
		let results = [];

		let name = $setting.attr("data-font-name");
		let current = $setting.find(".wrd-settings-font__current").val();
		let $results = $setting.find(".wrd-setting-font__results");

		$results.html("");

		search = search.toLowerCase().replace(/^\s+|\s+$/gm, "");

		for (let i = 0; i < WRD_G_FONTS_DATA.length; i++) {
			let font = WRD_G_FONTS_DATA[i];
			let family = font.family.toLowerCase().replace(/^\s+|\s+$/gm, "");

			if (family.includes(search) || search == "") {
				results.push(font);
			}
		}

		let total = results.length;
		let numPages = Math.ceil(total / perPage);

		if (page > numPages) {
			page = numPages;
		}

		if (page < 1) {
			page = 1;
		}

		let pageStart = perPage * (page - 1);
		let pageEnd = perPage * page;
		let pageResults = results.slice(pageStart, pageEnd);

		for (let i = 0; i < pageResults.length; i++) {
			let checked = current == pageResults[i].family;
			$results.append(create_font_item(pageResults[i], name, checked));
		}

		$setting.attr("data-page", page);
		$setting.find(".wrd-settings-font__page").text(page);
		$setting.find(".wrd-settings-font__total").text(numPages);
	}

	function create_font_item(font, name, checked = false) {
		let font_url = get_google_fonts_url(font.family);
		let id =
			"wrd-setting-font-" +
			font.family.toLowerCase().replace(/ /g, "") +
			"-" +
			Math.floor(Math.random() * 1000);

		let $item = $(`
            <div class='wrd-setting-font__choice'>

                <link rel='stylesheet' href='${font_url}'>

                <input id='${id}' type='radio' class='wrd-setting-font__input' name='${name}' value='${
			font.family
		}' ${checked ? "checked" : ""}>

                <label for='${id}' class='wrd-setting-font__label'>
                    <h4 style='font-family: ${font.family}'>
                        ${font.family}
                    </h4>
                    <span>
                        ${font.variants.length}
                        ${font.variants.length > 1 ? "Styles" : "Style"}
                    </span>
                </label>
            </div>
        `);

		return $item;
	}

	function get_google_fonts_url(family) {
		let url = new URL("https://fonts.googleapis.com/css");
		let data = {
			display: "swap",
			family: family,
			text: family,
		};

		for (const [key, value] of Object.entries(data)) {
			url.searchParams.set(key, value);
		}

		return url;
	}

	$(".wrd-setting-font").on("change", ".wrd-setting-font__input", function () {
		let $setting = $(this).closest(".wrd-setting");
		let val = $setting.find(".wrd-setting-font__input:checked").val();
		let $inp = $setting.find(".wrd-settings-font__current");

		$inp.val(val);

		let $preview = $setting.find(".wrd-setting-font__preview");
		let font_url = get_google_fonts_url(val);

		$preview.html(`<h4 style='font-family: ${val}'>${val}</h4>`);
		$preview.append(`<link rel='stylesheet' href='${font_url}'>`);
	});

	$(".wrd-setting-font__search").on("change keyup focusout", function (e) {
		let $setting = $(this).closest(".wrd-setting");
		let search = $(this).val();

		search_fonts($setting, search, 1);

		if (e.key == "Enter") {
			e.preventDefault();
		}
	});

	$(".wrd-settings-font__prev").on("click keypress", function (e) {
		if (e.type == "click" || e.key == " " || e.key == "Enter") {
			let $setting = $(this).closest(".wrd-setting");
			change_fonts_page($setting, -1);
		}
	});

	$(".wrd-settings-font__next").on("click keypress", function (e) {
		if (e.type == "click" || e.key == " " || e.key == "Enter") {
			let $setting = $(this).closest(".wrd-setting");
			change_fonts_page($setting, 1);
		}
	});

	$(".wrd-setting-font").each(function () {
		search_fonts($(this), "", 1);
	});
});
