<script type="text/javascript">

    (function($){
    $.fn.shortPass = '<?php echo $data['l10n']->get("password too short"); ?>';
    $.fn.badPass = '<?php echo $data['l10n']->get("password weak"); ?>';
    $.fn.goodPass = '<?php echo $data['l10n']->get("password good"); ?>';
    $.fn.strongPass = '<?php echo $data['l10n']->get("password strong"); ?>';
    $.fn.samePassword = '<?php echo $data['l10n']->get("username and password identical"); ?>';
    $.fn.resultStyle = "";

    $.fn.passStrength = function(options)
    {
        var defaults =
        {
            shortPass:      		"org_openpsa_user_shortPass",    //optional
            badPass:        		"org_openpsa_user_badPass",      //optional
            goodPass:       		"org_openpsa_user_goodPass",     //optional
            strongPass:     		"org_openpsa_user_strongPass",   //optional
            baseStyle:      		"testresult",   //optional
            userid:         		"",             //required override
            userid_required: 		false,			//optional (true for create/edit account)
            password_switch_id: 	"",				//optional
            messageloc:     		1               //before == 0 or after == 1
        };
        var opts = $.extend(defaults, options);
        opts.passwordid = 'input[name="'+$(this).attr("name")+'"]';
        opts.submit_button = 'input[name="midcom_helper_datamanager2_save"]';

        //run the check function once at start
        $.fn.setButtonStatus(opts);

        //now bind it to the controls..
        //bind check on username field
        $(opts.userid).bind('keyup',function()
        {
            $.fn.setButtonStatus(opts);
        });

        //bind check on password_switch if given
        if (opts.password_switch_id != "")
        {
            $(opts.password_switch_id).bind('change',function()
	    {
                $.fn.setButtonStatus(opts);
            });
        }

        return this.each(function()
        {
            var obj = $(this);

            //bind check on password field
            $(obj).bind('keyup',function()
            {
                $.fn.setButtonStatus(opts);

                var results = $.fn.teststrength($(this).val(),$(opts.userid).val(),opts);

                if (opts.messageloc === 1)
                {
                    $(this).next("." + opts.baseStyle).remove();
                    $(this).after("<span class=\""+opts.baseStyle+"\" style=\"padding-left:10px;\"><span></span></span>");
                    $(this).next("." + opts.baseStyle).addClass($(this).resultStyle).find("span").text(results);
                }
                else
                {
                    $(this).prev("." + opts.baseStyle).remove();
                    $(this).before("<span class=\""+opts.baseStyle+"\" style=\"padding-left:10px;\"><span></span></span>");
                    $(this).prev("." + opts.baseStyle).addClass($(this).resultStyle).find("span").text(results);
                }
            });
        });
     };
})(jQuery);

//FUNCTIONS
$.fn.teststrength = function(password, username, option)
{
    var score = 0;

    //password <
    if (password.length < <?php echo $data['min_length'];?>)
    {
    	this.resultStyle = option.shortPass;
    	return $(this).shortPass;
    }

    //password == user name
    if (password.toLowerCase() == username.toLowerCase())
    {
    	this.resultStyle = option.badPass;
    	return $(this).samePassword;
    }

    //password length
    score += password.length * 4;
    score += ($.fn.checkRepetition(1,password).length - password.length) * 1;
    score += ($.fn.checkRepetition(2,password).length - password.length) * 1;
    score += ($.fn.checkRepetition(3,password).length - password.length) * 1;
    score += ($.fn.checkRepetition(4,password).length - password.length) * 1;

    <?php
        foreach ($data['password_rules'] as $rule)
        {
            echo " if (password.match(".$rule['match'].")){ score += ".$rule['score'].";}";
        }
    ?>

    //verifying 0 < score < 100
    if (score < 0)
    {
    	score = 0;
    }
    if (score > 100)
    {
    	score = 100;
    }

    if (score < <?php echo $data['min_score'];?>)
    {
    	this.resultStyle = option.badPass;
    	return $(this).badPass;
    }

    if (score >= <?php echo $data['min_score'];?>)
    {
    	this.resultStyle = option.goodPass;
    	return $(this).goodPass;
    }

    this.resultStyle= option.strongPass;

    return $(this).strongPass;
};

$.fn.setButtonStatus = function(opts)
{
    var check_password = true;

    //check if we need to check the visibility of the password field
    if (opts.password_switch_id != "")
    {
        check_password = ($("#password_row").css("display") != "none");
    }

    //on edit form, only check the password strength if the field is not empty (second condition)
    if (   (opts.password_switch_id != "")
        || (opts.password_switch_id == "" && $(opts.passwordid).val() != ""))
    {
        //check password strength
        //if its empty, this will fail
        strength = $.fn.teststrength(
            $(opts.passwordid).val(),
            $(opts.userid).val(),
            opts);

        check_password = (check_password && !(strength == $.fn.goodPass || strength == $.fn.strongPass));
    }

    //on edit from with no password given
    if (opts.password_switch_id == "" && $(opts.passwordid).val() == ""){
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
        $(opts.submit_button).attr("disabled","disabled");
    }
    else
    {
        $(opts.submit_button).removeAttr("disabled");
    }
};

$.fn.checkRepetition = function(pLen, str)
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
</script>
