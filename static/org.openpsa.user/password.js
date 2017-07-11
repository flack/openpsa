(function($) {
    var classes = {
        shortPass: "org_openpsa_user_shortPass",
        badPass: "org_openpsa_user_badPass",
        goodPass: "org_openpsa_user_goodPass",
        strongPass: "org_openpsa_user_strongPass"
    },
    resultStyle = "";

    $.fn.password_widget = function(options) {
        var defaults = {
                baseStyle:      		"testresult",   //optional
                password_switch_id: 	"",	//optional
                messageloc:     		1       //before == 0 or after == 1
            },
            opts = $.extend({}, defaults, options || {}),
            password_field = $(this);
        
        opts.passwordid = 'input[name="' + $(this).attr("name") + '"]';
        opts.userid = 'input[name="org_openpsa_user[username]"]';
        opts.submit_button = 'button.save';

        $('input[name="org_openpsa_user[password][switch]"]')
            .bind('change', function() {
                if ($('input[name="org_openpsa_user[password][switch]"]:checked').val() == 0) {
                    password_field.hide();
                }
                else {
                    password_field.show();
                }
            })
            .trigger('change');

        $(opts.userid).bind('keyup', function() {
            $.fn.setButtonStatus(opts);
        });
        //run the check function once at start
        setButtonStatus(opts);

        return this.each(function() {
            //bind check on password field
            $(this).bind('keyup', function() {
                setButtonStatus(opts);

                var results = teststrength($(this).val(), opts);

                if (opts.messageloc === 1) {
                    $(this).next("." + opts.baseStyle).remove();
                    $(this).after("<span class=\"" + opts.baseStyle + "\" style=\"padding-left:10px;\"><span></span></span>");
                    $(this).next("." + opts.baseStyle).addClass(resultStyle).find("span").text(results);
                }
                else {
                    $(this).prev("." + opts.baseStyle).remove();
                    $(this).before("<span class=\"" + opts.baseStyle + "\" style=\"padding-left:10px;\"><span></span></span>");
                    $(this).prev("." + opts.baseStyle).addClass(resultStyle).find("span").text(results);
                }
            });
        });
    };
    var teststrength = function(password, option)
    {
        var score = 0;

        //password <
        if (password.length < option.min_length) {
    	    resultStyle = classes.shortPass;
    	    return option.strings.shortPass;
        }

        if ($(option.userid).length > 0) {
            var username = $(option.userid).val();
            //password == user name
            if (password.toLowerCase() == username.toLowerCase()) {
    	        resultStyle = classes.badPass;
    	        return option.strings.samePassword;
            }
        }

        //password length
        score += password.length * 4;
        score += (checkRepetition(1, password).length - password.length) * 1;
        score += (checkRepetition(2, password).length - password.length) * 1;
        score += (checkRepetition(3, password).length - password.length) * 1;
        score += (checkRepetition(4, password).length - password.length) * 1;

        $.each(option.password_rules, function(i, rule) {
            var regex = rule.match.replace(/^\//, '').replace(/\/$/, '');

            if (password.match(regex)) {
                score += rule.score;
            }
        });

        //verifying 0 < score < 100
        score = Math.min(100, Math.max(0, score));

        if (score < option.min_score) {
    	    resultStyle = classes.badPass;
    	    return option.strings.badPass;
        }

        if (score >= option.min_score) {
    	    resultStyle = classes.goodPass;
    	    return option.strings.goodPass;
        }

        resultStyle = classes.strongPass;

        return option.strings.strongPass;
    };

    var setButtonStatus = function(opts) {
        var check_password = true;

        //on edit form, only check the password strength if the field is not empty (second condition)
        if (   (opts.password_switch_id != "")
            || (opts.password_switch_id == "" && $(opts.passwordid).val() != "")) {
            //check password strength
            //if its empty, this will fail
            var strength = teststrength($(opts.passwordid).val(), opts);

            check_password = (check_password && !(strength == $.fn.goodPass || strength == $.fn.strongPass));
        }

        //on edit from with no password given
        if (opts.password_switch_id == "" && $(opts.passwordid).val() == "")
        {
            check_password = false;
        }

        //check if username is given and password check is ok
        //determine wheter the submit button should be disabled
        var disabled = ($(opts.userid).val() != "" && check_password);

        //set or remove disabled attribute of the submit button accordingly
        $(opts.submit_button).prop("disabled", disabled);
    };

    var checkRepetition = function(pLen, str) {
        var res = "";
        for (var i = 0; i < str.length; i++) {
            var repeated = true;

            for (var j = 0; j < pLen && (j + i + pLen) < str.length; j++) {
                repeated = repeated && (str.charAt(j + i) == str.charAt(j + i + pLen));
            }
            if (j < pLen) {
                repeated = false;
            }
            if (repeated) {
                i += pLen - 1;
            }
            else {
                res += str.charAt(i);
            }
        }
        return res;
    };

})(jQuery);
