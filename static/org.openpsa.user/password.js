(function($)
{
    $.fn.resultStyle = "";

    var classes =
    {
        shortPass: "org_openpsa_user_shortPass",
        badPass: "org_openpsa_user_badPass",
        goodPass: "org_openpsa_user_goodPass",
        strongPass: "org_openpsa_user_strongPass"
    };

    $.fn.password_widget = function(options)
    {
        var defaults =
        {
            baseStyle:      		"testresult",   //optional
            userid_required: 		false,	//optional (true for create/edit account)
            password_switch_id: 	"",	//optional
            messageloc:     		1       //before == 0 or after == 1
        },
        opts = $.extend({}, defaults, options || {});
        password_field = $(this),
        opts.passwordid = 'input[name="' + $(this).attr("name") + '"]';
        opts.submit_button = 'input[name="midcom_helper_datamanager2_save"]';

        if ($('input[name="org_openpsa_user_person_account_password_switch"]').val() == 0)
        {
            password_field.hide();
        }
        $('input[name="org_openpsa_user_person_account_password_switch"]').bind('change', function()
        {
            if ($(this).val() == 0)
            {
                password_field.hide();
            }
            else
            {
                password_field.show();
            }
        });

        //run the check function once at start
        setButtonStatus(opts);

        return this.each(function()
        {
            //bind check on password field
            $(this).bind('keyup', function()
            {
                setButtonStatus(opts);

                var results = teststrength($(this).val(), opts);

                if (opts.messageloc === 1)
                {
                    $(this).next("." + opts.baseStyle).remove();
                    $(this).after("<span class=\"" + opts.baseStyle + "\" style=\"padding-left:10px;\"><span></span></span>");
                    $(this).next("." + opts.baseStyle).addClass($(this).resultStyle).find("span").text(results);
                }
                else
                {
                    $(this).prev("." + opts.baseStyle).remove();
                    $(this).before("<span class=\"" + opts.baseStyle + "\" style=\"padding-left:10px;\"><span></span></span>");
                    $(this).prev("." + opts.baseStyle).addClass($(this).resultStyle).find("span").text(results);
                }
            });
        });
    };
    var teststrength = function(password, option)
    {
        var score = 0;

        //password <
        if (password.length < option.min_length)
        {
    	    this.resultStyle = classes.shortPass;
    	    return org_openpsa_user_password_strings.shortPass;
        }

        if ($('input[name="username"]').length > 0)
        {
            var username = $('input[name="username"]').val();
            //password == user name
            if (password.toLowerCase() == username.toLowerCase())
            {
    	        this.resultStyle = classes.badPass;
    	        return org_openpsa_user_password_strings.samePassword;
            }
        }

        //password length
        score += password.length * 4;
        score += (checkRepetition(1, password).length - password.length) * 1;
        score += (checkRepetition(2, password).length - password.length) * 1;
        score += (checkRepetition(3, password).length - password.length) * 1;
        score += (checkRepetition(4, password).length - password.length) * 1;

        score += org_openpsa_user_password_rules(password);

        //verifying 0 < score < 100
        if (score < 0)
        {
    	    score = 0;
        }
        if (score > 100)
        {
    	    score = 100;
        }

        if (score < option.min_score)
        {
    	    this.resultStyle = classes.badPass;
    	    return org_openpsa_user_password_strings.badPass;
        }

        if (score >= option.min_score)
        {
    	    this.resultStyle = classes.goodPass;
    	    return org_openpsa_user_password_strings.goodPass;
        }

        this.resultStyle = classes.strongPass;

        return org_openpsa_user_password_strings.strongPass;
    };

    var setButtonStatus = function(opts)
    {
        var check_password = true;

        //on edit form, only check the password strength if the field is not empty (second condition)
        if (   (opts.password_switch_id != "")
            || (opts.password_switch_id == "" && $(opts.passwordid).val() != ""))
        {
            //check password strength
            //if its empty, this will fail
            strength = teststrength($(opts.passwordid).val(), opts);

            check_password = (check_password && !(strength == $.fn.goodPass || strength == $.fn.strongPass));
        }

        //on edit from with no password given
        if (opts.password_switch_id == "" && $(opts.passwordid).val() == "")
        {
            check_password = false;
        }

        //check if username is given and password check is ok
        //determine wheter the submit button should be disabled
        var disabled = true;
        if ($(opts.userid).val() != "" && check_password)
        {
            disabled = true;
        }
        else
        {
            //check for username seperatly, only if an userid is required
            if (opts.userid_required && $(opts.userid).val() == "")
            {
                disabled = true;
            }
            else
            {
                disabled = false;
            }
        }

        //set or remove disabled attribute of the submit button accordingly
        if (disabled)
        {
            $(opts.submit_button).prop("disabled", true);
        }
        else
        {
            $(opts.submit_button).prop("disabled", false);
        }
    };

    var checkRepetition = function(pLen, str)
    {
        var res = "";
        for (var i = 0; i < str.length; i++)
        {
            var repeated = true;

            for (var j = 0; j < pLen && (j + i + pLen) < str.length; j++)
            {
                repeated = repeated && (str.charAt(j + i) == str.charAt(j + i + pLen));
            }
            if (j < pLen)
            {
                repeated = false;
            }
            if (repeated)
            {
                i += pLen - 1;
                repeated = false;
            }
            else
            {
                res += str.charAt(i);
            }
        }
        return res;
    };
})(jQuery);
