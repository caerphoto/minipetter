/*global config:false */
$(function () {
    "use strict";

    var $popup_box = $("#popup_box"),
        $list_controls = $("#list_controls"),
        $list_filter = $("#list_filter"),
        $pet_list = $("#pet_list"),
        $main_wrapper = $("#main_wrapper"),
        $body = $(document.body),
        close_btn,

        $editor = $("#editor"),

        popup_tmpl = $("#popup_tmpl").html(),
        list_tmpl = $("#list_tmpl").html(),
        editor_tmpl = $("#editor_tmpl").html(),

        screen_size,
        last_scroll_pos,

        ajax_defaults = {
            url: "api.php",
            dataType: "json"
        },

        pet_data = [],
        pet_lookup = {},
        filtered_list = [],
        current_pet = {},

        prepArray,
        setupFilterOptions,
        deriveShortName,
        logIn, logOut,
        fetchPetData,
        buildLists, savePet, deletePet,
        showPopup, hidePopup, showEditor, hideEditor, showList, hideList;

    if (window.getComputedStyle) {
        screen_size = window.getComputedStyle(document.body,':after').getPropertyValue('content');
        screen_size = parseInt(screen_size.slice(1).slice(0, -1), 10);
    }

    prepArray = function (a) {
        // Takes an array of strings and turns it into an array of objects
        // containing the index and the string value, for use in Mustache
        // templates.
        $.each(a, function (idx, val) {
            a[idx] = { val: idx, txt: val };
        });
    };

    prepArray(config.types);
    prepArray(config.sources);
    prepArray(config.rarities);

    setupFilterOptions = function () {
        var html, tmpl, el,
            config_keys = ["types", "sources", "rarities"];

        $.each(["type", "source", "rarity"], function (idx, by) {
            var key = config_keys[idx];

            tmpl = document.getElementById("filter_by_" + by + "_tmpl").innerHTML;
            html = Mustache.to_html(tmpl, { options: config[key] });

            el = document.getElementById("filter_by_" + by);
            el.innerHTML = html;
        });
    };

    deriveShortName = function (long_name) {
        if (!long_name) {
            return "";
        }

        return long_name.
            toLowerCase().
            match(/[a-z \-]+/g).
            join("").
            replace(/[\s\-]/g, "_");
    };

    logIn = function () {
        var pw = $("#password").val();

        $("#password").val("");

        $.ajax({
            url: "login.php",
            type: "POST",
            data: { password: pw, action: "login" },
            success: function () {
                $body.addClass("logged_in");
                window.location.hash = current_pet.short_name || "";
            },
            error: function () {
                window.alert("Incorrect password.");
                $("#password").focus();
            }
        });
    };

    logOut = function () {
        $.ajax({
            url: "login.php",
            type: "POST",
            data: { action: "logout" },
            success: function () {
                $body.removeClass("logged_in");
                window.location.hash = current_pet.short_name || "";
            }
        });
    };

    fetchPetData = function (callback) {
        if (typeof callback !== "function") {
            return;
        }

        $.ajax({
            success: function (data) {
                callback(data);
            }
        });
    };

    buildLists = function (filter, options) {
        // (Re)construct HTML list and internal lookup table of pet data, using
        // the filter if it's given.
        var i, l, idx = 0,
            p,
            re;

        pet_lookup = {};
        filtered_list = [];

        if (filter && typeof filter === "string") {
            re = new RegExp(filter, "i");

            for (i = 0, l = pet_data.length; i < l; i += 1) {
                p = pet_data[i];

                if (re.test(p.long_name)) {
                    p.index = i;
                    p.filtered_index = idx;
                    pet_lookup[p.short_name] = p;
                    filtered_list.push(p);
                    idx += 1;
                }
            }

        } else {
            for (i = 0, l = pet_data.length; i < l; i += 1) {
                p = pet_data[i];
                p.index = p.filtered_index = i;
                pet_lookup[p.short_name] = p;
                filtered_list.push(p);
            }
        }

        for (i = 0, l = filtered_list.length; i < l; i += 1) {
            p = filtered_list[i];
            idx = +p.rarity;
            p.rarity_txt = config.rarities[idx].txt.toLowerCase();
        }

        $pet_list.html(Mustache.to_html(list_tmpl, { pet_data: filtered_list }));

        if (!screen_size && window.location.hash) {
            l = window.location.hash.slice(1);
            $pet_list.find("#pet_" + l).addClass("target");
        }

    };

    showPopup = function () {
        var $w = $(window),
            idx,
            info_txt,
            view = {};

        if (!current_pet || !current_pet.short_name) {
            return false;
        }

        last_scroll_pos = $w.scrollTop();

        document.title = current_pet.long_name + " \u00B7 Minipetter";

        // Transform numerical rarity (etc.) into text equivalent.
        $.extend(view, current_pet);
        idx = parseInt(current_pet.rarity, 10) || 0;
        view.rarity = config.rarities[idx].txt;
        idx = parseInt(current_pet.ob_via, 10) || 0;
        view.source = config.sources[idx].txt;
        idx = parseInt(current_pet.type, 10) || 0;
        view.type = config.types[idx].txt;

        // Convert "This is some info.  @@this is flavour text." into
        // appropriate HTML.
        info_txt = current_pet.info.split("@@");
        // Trim whitespace.
        view.info = info_txt[0].replace(/^\s*(.*)\s*$/, "$1");
        if (info_txt.length > 1) {
            view.flavour_text = info_txt[1].replace(/^\s*'?(.*)'\s*$/, '$1');
        }

        $popup_box.html(Mustache.to_html(popup_tmpl, view));

        $body.addClass("has_popup");
        $popup_box.addClass("visible");
        $editor.removeClass("visible");
        $pet_list.find(".target").removeClass("target");

        window.scrollTo(0, 1);


        $w = $("#pet_" + current_pet.short_name);
        $w.addClass("target");

        return true;
    };

    hidePopup = function () {
        $popup_box.removeClass("visible");
        $body.removeClass("has_popup");
    };

    savePet = function (is_new_pet, callback) {
        var f = document.getElementById("editor_form"),
            new_pet = {};

        if (!$body.hasClass("logged_in")) {
            return;
        }

        // Get values from form and store them in new_pet.
        $.each(config.fields, function (idx, val) {
            new_pet[val] = (f[val] || {}).value;
        });

        if (is_new_pet) {
            // Generate short_name automatically.
            // Maybe this is something the server should do?
            new_pet.short_name = deriveShortName(new_pet.long_name);
        } else {
            // Use existing short_name.
            new_pet.short_name = current_pet.short_name;
        }

        // TODO: validation goes here
        $.ajax({
            data: new_pet,
            type: "POST",

            success: function (saved_pet_data) {
                if (is_new_pet) {
                    pet_data.push(saved_pet_data);
                    pet_data.sort(function (a, b) {
                        return a.short_name < b.short_name ? -1 : 1;
                    });
                } else {
                    $.extend(current_pet, saved_pet_data);
                }

                buildLists($list_filter.val());

                if (typeof callback === "function") {
                    callback(saved_pet_data);
                }
            }
        });
    };

    deletePet = function () {
        if (!$body.hasClass("logged_in")) {
            return;
        }

        $.ajax({
            data: {
                short_name: current_pet.short_name,
                action: "delete"
            },

            success: function () {
                var next_index, $pet;

                if (current_pet.filtered_index > 0) {
                    next_index = current_pet.filtered_index + 1;
                } else {
                    next_index = 0;
                }

                pet_data.splice(current_pet.index, 1);
                filtered_list.splice(current_pet.filtered_index, 1);

                if (next_index > filtered_list.length - 1) {
                    next_index = filtered_list.length - 1;
                }

                $pet = $("#pet_" + current_pet.short_name);
                $pet.fadeOut(function () {
                    $pet.remove();
                    window.location.hash = filtered_list[next_index].short_name;
                });
            }
        });
    };

    showEditor = function (is_new_pet) {
        var view = {
                sources: config.sources,
                rarities: config.rarities,
                types: config.types
            },
            f;

        if (!$body.hasClass("logged_in")) {
            return;
        }

        if (is_new_pet) {
            document.title = "Add pet \u00B7 Minipetter";
        } else {
            document.title = "Edit: " + current_pet.long_name +
                " \u00B7 Minipetter";
        }

        if (is_new_pet) {
            // Give the Cancel button something to go back to.
            view.cancel_to = (current_pet || {}).short_name;
        } else {
            $.extend(view, current_pet);
        }

        view.info = view.info.replace(/<BR><BR>/, "\n\n@@");

        $editor.html(Mustache.to_html(editor_tmpl, view));

        // Annoyingly you can't specify a <select>'s value in a template, as you
        // have to set the 'selected' property on one of its <option> children.
        if (!is_new_pet) {
            f = document.getElementById("editor_form");
            f.type.value =  current_pet.type || 0;
            f.ob_via.value =  current_pet.ob_via || 0;
            f.rarity.value =  current_pet.rarity || 0;
        }

        $editor.addClass("visible");
        $editor.toggleClass("is_new_pet", !!is_new_pet);

        $editor.find("input[name='long_name']").focus();
    };

    hideEditor = function () {
        $editor.removeClass("visible");
        if (document.activeElement !== document.body) {
            document.activeElement.blur();
        }
    };

    showList = function () {
        $(window).scrollTop(last_scroll_pos);
        $pet_list.addClass("visible");
        $list_controls.addClass("visible");
    };

    hideList = function () {
        $pet_list.removeClass("visible");
        $list_controls.removeClass("visible");
    };

    // Main hash-based route-handling function.
    $(window).bind("hashchange", function (evt) {
        var h = window.location.hash,
            route, action;

        if (!h) {
            showList();
            hidePopup();
            hideEditor();
            $pet_list.find(".target").removeClass("target");
            document.title = "Minipetter";
            return;
        }

        if (evt && typeof evt.preventDefault === "function") {
            evt.preventDefault();
        }

        route = h.split("/");

        // Remove leading # character
        route[0] = route[0].slice(1);
        action = route.length === 1 ? route[0] : route[1];

        switch (action) {
            case "add":
                hideList();
                hidePopup();
                showEditor(true);
                break;
            case "edit":
                current_pet = pet_lookup[route[0]];
                hideList();
                hidePopup();
                showEditor();
                break;
            case "save":
                current_pet = pet_lookup[route[0]];
                savePet(false, function (updated_pet) {
                    window.location.hash = updated_pet.short_name;
                });
                break;
            case "create":
                savePet(true, function (new_pet) {
                    window.location.hash = new_pet.short_name;
                });
                break;
            case "delete":
                current_pet = pet_lookup[route[0]];
                if (current_pet &&
                    window.confirm("Are you sure you want to delete the " +
                    current_pet.long_name + " pet?")) {

                    deletePet();
                }
                break;
            case "log_in":
                logIn();
                break;
            case "log_out":
                logOut();
                break;
            default:
                // Simply show pet info.
                current_pet = pet_lookup[route[0]] || {};
                hideEditor();
                showPopup();
                showList(); // might still be hidden if on a phone
        }
    });

    $list_filter.keyup(function () {
        buildLists(this.value);
    }).click(function () {
        // handle menu-based input, and clicks on the 'clear text' button
        buildLists(this.value);
    });

    $editor.delegate("textarea", "focus", function () {
        $(this.parentNode).addClass("focus");
    }).delegate("textarea", "blur", function () {
        $(this.parentNode).removeClass("focus");
    }).delegate("#long_name_input", "keyup", function() {
        var sn = deriveShortName(this.value);

        if (!$editor.hasClass("is_new_pet")) {
            return;
        }

        $("#sn").toggleClass("already_exists", !!pet_lookup[sn]).
            text(sn);
    });

    $body.keydown(function (evt) {
        var k = evt.keyCode;
        // Enable keyboard navigation of list
        if (document.activeElement !== document.body) {
            return;
        }

        // Keys:
        // 38: up arrow
        // 40: down arrow

        if (k === 38 && current_pet.filtered_index > 0) {
            window.location.hash = filtered_list[current_pet.filtered_index -
                1].short_name;

        } else if (k === 40 && current_pet.filtered_index + 1 <
            filtered_list.length) {

            window.location.hash = filtered_list[current_pet.filtered_index +
                1].short_name;
        }

        // Next bits are only relevant if the user is logged in.
        if (!$body.hasClass("logged_in")) {
            return;
        }

        // 65: a
        // 69: e
        // 78: n

        if ((k === 65 || k === 78) && $body.hasClass("logged_in") && 
            !$editor.hasClass("visible")) {
            evt.preventDefault();
            window.location.hash = "add";
        } else if (k === 69 && $popup_box.hasClass("visible")) {
            evt.preventDefault();
            window.location.hash = current_pet.short_name + "/edit";
        }
    });

    $editor.keydown(function (evt) {
        // metaKey = Cmd on OS X
        if (evt.keyCode === 13 && (evt.ctrlKey || evt.metaKey)) {
            window.location.hash = current_pet.short_name + "/save";
        } else if (evt.keyCode === 27) {
            window.location.hash = current_pet.short_name || "";
        }

    });

    $.ajaxSetup(ajax_defaults);
    setTimeout(function (){
        // Hide the address bar
        window.scrollTo(0, 1);
    }, 0);

    setupFilterOptions();

    // And findally, load pet data and get the ball rolling.
    fetchPetData(function (data) {
        pet_data = data;

        buildLists();
        $(window).trigger("hashchange");
    });

});
