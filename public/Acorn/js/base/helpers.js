/**
 *
 * Helpers
 * Static helper methods.
 *
 */

class Helpers {
    // A basic debounce function for events like resize, keydown and etc.
    static Debounce(func, wait, immediate) {
        var timeout;
        return function () {
            var context = this,
                args = arguments;
            var later = function () {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    // Checks the given array and returns a value plus one from the greatest prop value
    static NextId(data, prop) {
        if (!data) {
            console.error("NextId data is null");
            return;
        }
        const max = data.reduce(function (prev, current) {
            if (+parseInt(current[prop]) > +parseInt(prev[prop])) {
                return current;
            } else {
                return prev;
            }
        });
        return parseInt(max[prop]) + 1;
    }

    // Fetches data from the path parameter and fires onComplete callback with the json formatted data
    static FetchJSON(path, onComplete) {
        fetch(path)
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response;
            })
            .then((response) => response.json())
            .then((data) => onComplete(data))
            .catch((error) => {
                console.error("Problem with the fetching JSON data: ", error);
            });
    }

    // Adds commas to thousand
    static AddCommas(nStr) {
        nStr += "";
        var x = nStr.split(".");
        var x1 = x[0];
        var x2 = x.length > 1 ? "." + x[1] : "";
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, "$1" + "," + "$2");
        }
        return x1 + x2;
    }

    // If the project is run in a subdirectory and absolute-path is used, this function adds the data-url-prefix value defined in the html element to the paths.
    static UrlFix(paramPath) {
        const dataPrefix = document.documentElement.dataset.urlPrefix;
        if (!dataPrefix) {
            return paramPath;
        }
        const prefix = dataPrefix.endsWith("/") ? dataPrefix : `${dataPrefix}/`;
        const path = paramPath.startsWith("/")
            ? paramPath.slice(1, paramPath.length)
            : paramPath;
        return `${prefix}${path}`;
    }

    static ajaxSelect2(element, options) {
        let defaultOptions = {
            ajax: {
                url: options?.url,
                dataType: options?.dataType ?? "json",
                delay: options?.delay ?? 250,
                data: function (params) {
                    return {
                        search: {
                            value: params.term,
                        },
                        page: params.page,
                    };
                },
                processResults: function (data, page) {
                    return {
                        results: data,
                    };
                },
                cache: false,
            },
            allowClear: true,
            width: "resolve",
            placeholder: options?.placeholder ?? "Search",
            escapeMarkup: function (markup) {
                return markup;
            },
            templateResult: function formatResult(result) {
                if (result.loading) return result.text;
                var markup =
                    '<div class="clearfix"> <div class="select2title"> ' +
                    result.text +
                    " </div>";
                if (result.description) {
                    markup +=
                        '<div class="text-muted">' +
                        result.description +
                        "</div>";
                }
                return markup;
            },
            templateSelection: function (data, container) {
                if (data?.data_attributes?.length > 0) {
                    $.each(data?.data_attributes, (i, v) => {
                        $(data.element).attr(i, v);
                    });
                }
                return data.text;
            },
        };
        if (options?.multiple) {
            defaultOptions.multiple = true;
        }
        const dropdownParent = $(element).parents(".modal.show:first");
        if ($(dropdownParent).length > 0) {
            defaultOptions["dropdownParent"] = dropdownParent;
        }
        jQuery(element).select2(defaultOptions);
    }

    static countDaysOfDates(startDate, endDate = null, sameDay = true) {
        const start = moment(startDate, "DD-MM-YYYY");
        const end = endDate ? moment(endDate, "DD-MM-YYYY") : moment();

        const diff = end.diff(start, "days");
        if (diff === 0 && sameDay) {
            return 1;
        }
        return diff + 1;
    }
    static initializeTinyMCE(selector, customOptions) {
        const defaultOptions = {
            selector: selector,
            plugins:
                "preview importcss searchreplace autolink autosave save directionality visualblocks visualchars fullscreen image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help quickbars emoticons",
            mobile: {
                plugins:
                    "preview importcss searchreplace autolink autosave save directionality visualblocks visualchars fullscreen image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help quickbars emoticons",
            },
            menubar: "file edit view insert format tools table tc help",
            toolbar:
                "undo redo | revisionhistory | aidialog aishortcuts | blocks fontsizeinput | bold italic | align numlist bullist | link image | table math media pageembed | lineheight outdent indent | strikethrough forecolor backcolor formatpainter removeformat | charmap emoticons checklist | code fullscreen preview | save print | pagebreak anchor codesample footnotes mergetags | addtemplate inserttemplate | addcomment showcomments | ltr rtl casechange | spellcheckdialog a11ycheck",
            image_advtab: true,
            importcss_append: true,
            image_caption: true,
            quickbars_selection_toolbar:
                "bold italic | quicklink h2 h3 blockquote quickimage quicktable",
            noneditable_class: "mceNonEditable",
            toolbar_mode: "sliding",
            spellchecker_ignore_list: [
                "Ephox",
                "Moxiecode",
                "tinymce",
                "TinyMCE",
            ],
            tinycomments_mode: "embedded",
            content_style: ".mymention{ color: gray; }",
            contextmenu: "link image editimage table configurepermanentpen",
            a11y_advanced_options: true,
            height: 400,
            setup: function (editor) {
                editor.on("change keyup", function () {
                    document.querySelector(selector).textContent =
                        editor.getContent();
                });
            },
        };

        const finalOptions = Object.assign({}, defaultOptions, customOptions);

        tinymce.init(finalOptions);
    }
}
