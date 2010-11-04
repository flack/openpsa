
    <script type="text/javascript">

    (function($){
    $.fn.shortPass = '<?php echo $data['l10n']->get("password too short"); ?>';
    $.fn.badPass = '<?php echo $data['l10n']->get("password weak"); ?>';
    $.fn.goodPass = '<?php echo $data['l10n']->get("password good"); ?>';
    $.fn.strongPass = '<?php echo $data['l10n']->get("password strong"); ?>';
    $.fn.samePassword = '<?php echo $data['l10n']->get("username and password identical"); ?>';
    $.fn.resultStyle = "";

     $.fn.passStrength = function(options) {

         var defaults = {
                shortPass:      "shortPass",    //optional
                badPass:        "badPass",      //optional
                goodPass:       "goodPass",     //optional
                strongPass:     "strongPass",   //optional
                baseStyle:      "testresult",   //optional
                userid:         "",             //required override
                messageloc:     1               //before == 0 or after == 1
            };
            var opts = $.extend(defaults, options);

            return this.each(function() {
                 var obj = $(this);

                $(obj).unbind().keyup(function()
                {

                    var results = $.fn.teststrength($(this).val(),$(opts.userid).val(),opts);

                    if(opts.messageloc === 1)
                    {
                        $(this).next("." + opts.baseStyle).remove();
                        $(this).after("<span class=\""+opts.baseStyle+"\" style=\"padding-left:10px;color:red;\"><span></span></span>");
                        $(this).next("." + opts.baseStyle).addClass($(this).resultStyle).find("span").text(results);
                    }
                    else
                    {
                        $(this).prev("." + opts.baseStyle).remove();
                        $(this).before("<span class=\""+opts.baseStyle+"\" style=\"padding-left:10px;color:red;\"><span></span></span>");
                        $(this).prev("." + opts.baseStyle).addClass($(this).resultStyle).find("span").text(results);
                    }
                 });

                //FUNCTIONS
                $.fn.teststrength = function(password,username,option){
                        var score = 0;
                        $("#submit_account").attr("disabled" , "disabled");
                        //password <
                        if (password.length < <?php echo $data['min_length'];?> ) { this.resultStyle =  option.shortPass;return $(this).shortPass; }

                        //password == user name
                        if (password.toLowerCase()==username.toLowerCase()){this.resultStyle = option.badPass;return $(this).samePassword;}

                        //password length
                        score += password.length * 4;
                        score += ( $.fn.checkRepetition(1,password).length - password.length ) * 1;
                        score += ( $.fn.checkRepetition(2,password).length - password.length ) * 1;
                        score += ( $.fn.checkRepetition(3,password).length - password.length ) * 1;
                        score += ( $.fn.checkRepetition(4,password).length - password.length ) * 1;

                        <?php

                        foreach($data['password_rules'] as $rule)
                        {
                            echo " if (password.match(".$rule['match'].")){ score += ".$rule['score'].";}";
                        }
                        ?>

                        //verifying 0 < score < 100
                        if ( score < 0 ){score = 0;}
                        if ( score > 100 ){  score = 100;}

                        if (score < <?php echo $data['min_score'];?> )
                        {
                            this.resultStyle = option.badPass; return $(this).badPass;
                        }
                        if (score >= <?php echo $data['min_score'];?> )
                        {
                            $("#submit_account").removeAttr("disabled");
                            this.resultStyle = option.goodPass;return $(this).goodPass;
                        }

                       this.resultStyle= option.strongPass;
                       return $(this).strongPass;

                };

          });
     };
})(jQuery);

$.fn.checkRepetition = function(pLen,str) {
    var res = "";
     for (var i=0; i<str.length ; i++ )
     {
         var repeated=true;

         for (var j=0;j < pLen && (j+i+pLen) < str.length;j++){
             repeated=repeated && (str.charAt(j+i)==str.charAt(j+i+pLen));
             }
         if (j<pLen){repeated=false;}
         if (repeated) {
             i+=pLen-1;
             repeated=false;
         }
         else {
             res+=str.charAt(i);
         }
     }
     return res;
    };



    </script>
