<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id$
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Multilang service.
 *
 * This service contains add-on functionality for Midgard's built-in multilang
 * support.
 *
 * There are two automated/transparent workflows provided by this service. Both
 * workflows modify object deletions to delete full objects instead of just
 * translations. So basically the included languages are all master languages.
 *
 * lang0:
 * The idea of this workflow is to automatically put best matching language
 * content to lang0 as a fallback. (The order of the languages matters.)
 * (Copying exactly same content as in the best matching language deletes
 * the translation.)
 *
 * auto:
 * The idea of this workflow is to automatically create other untranslated
 * language contents and keep them in sync with best matching translated
 * language content. (The order of the languages matters.) Translating
 * detaches a language content from the syncing. (And copying exactly same
 * content as in the best matching translated language re-attaches the
 * language content to the syncing.)
 *
 *
 * @TODO:
 * We use midgard_object_class::connect_default() which is apparently something
 * which can be used only once so please *if* something else needs it too, we
 * need to refactor it somehow in a way it can be used by multiple services.
 * Which means it needs to have its own service or something...
 * It will probably be implemented on php5-midgard level:
 * http://trac.midgard-project.org/ticket/1833
 *
 * @package midcom.services
 */
class midcom_services_multilang
{
    // $_MIDCOM free methods start.
    // These methods are used before $_MIDCOM is initialized and they can't
    // therefore contain anything $_MIDCOM specific. These "low level" methods
    // are used in auto workflows.

    function auto($auto_langs = null, $lang0_langs = null)
    {
        static $enabled = false;

        if ($enabled) return false;

        if ($auto_langs) $GLOBALS['midcom_config']['multilang_auto_langs'] = $auto_langs;
        if ($lang0_langs) $GLOBALS['midcom_config']['multilang_lang0_langs'] = $lang0_langs;

        foreach ($_MIDGARD['schema']['types'] as $type => $value)
        {
            if (midgard_object_class::is_multilang($type))
            {
                midgard_object_class::connect_default
                (
                    $type,
                    'action_created',
                    array
                    (
                        __CLASS__,
                        'syncs',
                    ),
                    array
                    (
                        false,
                    )
                );
                
                midgard_object_class::connect_default
                (
                    $type,
                    'action_updated',
                    array
                    (
                        __CLASS__,
                        'syncs',
                    ),
                    array
                    (
                        true,
                    )
                );

                midgard_object_class::connect_default
                (
                    $type,
                    'action_delete',
                    array
                    (
                        __CLASS__,
                        'set_langs',
                    )
                );

                midgard_object_class::connect_default
                (
                    $type,
                    'action_deleted',
                    array
                    (
                        __CLASS__,
                        'set_langs_back',
                    )
                );
            }
        }

        $enabled = true;

        return $enabled;
    }

    function langs($include_langs)
    {
        if (!$include_langs)
        {
            return null;
        }

        $langs = array();
        foreach ($include_langs as $lang)
        {
            $langs[$lang] = true;
        }
        return $langs;
    }

    function get_lang()
    {
        $lang = midgard_connection::get_lang();
        
        if (!$lang)
        {
            $lang = '';
        }
        
        return $lang;
    }

    function get_default_lang()
    {
        $lang = midgard_connection::get_default_lang();

        if (!$lang)
        {
            $lang = '';
        }

        return $lang;
    }

    function is_real($new_status = null)
    {
        static $status = true;
        
        if (!is_null($new_status))
        {
            $status = $new_status;
        }
        
        return $status;
    }

    function get_object_in_lang($object, $lang, $strict = false)
    {
        if (is_a($object, 'midcom_core_dbaobject'))
        {
            $object = $object->__object;
        }

        if (!midgard_object_class::is_multilang(get_class($object)))
        {
            return $object;
        }

        $real_lang = self::get_lang();
        $object_in_lang = null;
        
        midgard_connection::set_lang($lang);
        try
        {
            $class = get_class($object);
            $object_in_lang = new $class($object->guid);
            if ($strict)
            {
                $object_lang = null;
                if (!$object_in_lang->lang)
                {
                    $object_lang = '';
                }
                else
                {
                    try
                    {
                        $language = new midgard_language($object_in_lang->lang);
                        $object_lang = $language->code;
                    }
                    catch (Exception $e)
                    {
                        // Nothing to do. If DB is missing the language, strict fails then.
                    }
                }

                if ($object_lang != $lang)
                {
                    $object_in_lang = null;
                }
            }
        }
        catch (Exception $e)
        {
            // Nothing to do. Object doesn't exist in lang.
        }
        midgard_connection::set_lang($real_lang);

        return $object_in_lang;
    }

    function are_objects_equal($object1, $object2)
    {
        if (is_a($object1, 'midcom_core_dbaobject'))
        {
            $object1 = $object1->__object;
        }
        if (is_a($object2, 'midcom_core_dbaobject'))
        {
            $object2 = $object2->__object;
        }

        $equal = true;

        foreach (get_object_vars($object1) as $key => $value)
        {
            if (   $key != 'metadata'
                && $key != 'lang'
                && $value != $object2->$key)
            {
                $equal = false;
                break;
            }
        }

        return $equal;
    }

    function is_object_equal_in_lang($object, $lang, $strict = false)
    {
        if (is_a($object, 'midcom_core_dbaobject'))
        {
            $object = $object->__object;
        }

        if (!midgard_object_class::is_multilang(get_class($object)))
        {
            return true;
        }

        $equal = false;

        if ($object_in_lang = self::get_object_in_lang($object, $lang, $strict))
        {
            $equal = self::are_objects_equal($object, $object_in_lang);
        }

        return $equal;
    }

    function sync($object, $langs, $domain, $is_update, $lang0 = false)
    {
        if (!self::is_real()) return;

        if (is_a($object, 'midcom_core_dbaobject'))
        {
            $object = $object->__object;
        }

        if (!midgard_object_class::is_multilang(get_class($object)))
        {
            return;
        }

        $real_lang = self::get_lang();
        if (!isset($langs[$real_lang])) return;

        if ($is_update)
        {
            $parent = null;
            $lang_exists = false;
            $lang0_exists = false;

            if ($languages = $object->get_languages())
            {
                foreach ($languages as $language)
                {
                    if (isset($langs[$language->code]))
                    {
                        if (!$object->get_parameter($domain, $language->code))
                        {
                            $langs[$language->code] = false;
                        }
                        else if (!$lang0)
                        {
                            if (   is_null($parent)
                                && $language->id != $object->lang)
                            {
                                $parent = $language->code;
                            }
                        }
                    }

                    if (   $language->code == $real_lang
                        && $object->lang == $language->id)
                    {
                        $lang_exists = true;
                    }
                    if ($language->id == 0)
                    {
                        $lang0_exists = true;
                    }
                }

                if (!$lang0)
                {
                    if (!$langs[$real_lang])
                    {
                        $parent = null;
                    }
                }
            }

            $detach = true;

            foreach ($langs as $lang => $enabled)
            {
                if ($lang == $real_lang)
                {
                    continue;
                }

                if (!$enabled)
                {
                    $parent = $lang;
                    break;
                }
            }
            
            if (!is_null($parent))
            {
                $detach = !self::is_object_equal_in_lang($object, $parent);
            }
            else
            {
                if ($lang0)
                {
                    if ($langs[$real_lang])
                    {
                        $detach = false;
                    }
                }
            }

            if ($detach)
            {
                if (  !(   $lang0
                        && $GLOBALS['midcom_config']['multilang_auto_langs']
                        && in_array($real_lang, $GLOBALS['midcom_config']['multilang_auto_langs']))
                   )
                {
                    $constraints = array
                    (
                        'domain' => $domain,
                        'name' => $real_lang,
                    );
                    $object->purge_parameters($constraints);
                }
            }
            else
            {
                if ($lang0)
                {
                    if (  !(   $GLOBALS['midcom_config']['multilang_auto_langs']
                            && in_array($real_lang, $GLOBALS['midcom_config']['multilang_auto_langs']))
                       )
                    {
                        $constraints = array
                        (
                            'domain' => $domain,
                            'name' => $real_lang,
                        );
                        $object->purge_parameters($constraints);

                        if (   $real_lang
                            && $lang_exists
                            && !midgard_connection::get_default_lang())
                        {
                            if ($lang0 === true && !$lang0_exists)
                            {
                                self::is_real(false);
                                midgard_connection::set_lang('');
                                $object->update();
                                midgard_connection::set_lang($real_lang);
                                self::is_real(true);

                                $lang0_exists = true;
                            }

                            if ($lang0_exists)
                            {
                                self::is_real(false);
                                self::set_default_lang_to_lang0();
                                $object->delete();
                                self::set_default_lang_back();
                                self::is_real(true);

                                $lang_exists = false;
                            }
                        }
                    }
                }
                else
                {
                    $object->set_parameter($domain, $real_lang, true);
                }
            }

            foreach ($langs as $lang => $enabled)
            {
                if ($lang == $real_lang)
                {
                    break;
                }
                
                if (!$enabled)
                {
                    return;
                }
            }

            if (!$lang0)
            {
                if (!$lang_exists)
                {
                    if (   !$object->lang
                        && !isset($langs[''])
                        && !$GLOBALS['midcom_config']['multilang_lang0_langs'])
                    {
                        self::is_real(false);
                        $object->update();
                        self::is_real(true);

                        $lang_exists = true;

                        $object->set_parameter($domain, $real_lang, true);
                    }
                    else
                    {
                        return;
                    }
                }
            }
        }

        self::is_real(false);
        if ($lang0)
        {
            if ($lang0 === true && $real_lang)
            {
                midgard_connection::set_lang('');
                $object->update();
                midgard_connection::set_lang($real_lang);
            }
        }
        else
        {
            foreach ($langs as $lang => $enabled)
            {
                if ($enabled)
                {
                    midgard_connection::set_lang($lang);
                    $object->update();
                    $object->set_parameter($domain, $lang, true);
                }
            }
            midgard_connection::set_lang($real_lang);
        }
        self::is_real(true);
    }

    function syncs($object, $is_update = true)
    {
        if (!self::is_real()) return;

        $auto = true;
        if (is_null($is_update))
        {
            $is_update = true;
            $auto = false;
        }

        static $domain;
        if (!isset($domain))
        {
            $domain = str_replace('_', '.', __CLASS__) . '.' . 'auto';
        }

        if ($auto)
        {
            if ($GLOBALS['midcom_config']['multilang_auto_langs'])
            {
                self::sync($object, self::langs($GLOBALS['midcom_config']['multilang_auto_langs']), $domain, $is_update, false);
            }

            if ($GLOBALS['midcom_config']['multilang_lang0_langs'])
            {
                self::sync($object, self::langs($GLOBALS['midcom_config']['multilang_lang0_langs']), $domain, $is_update, true);
            }
        }
        else
        {
            if (is_a($object, 'midcom_core_dbaobject'))
            {
                $object = $object->__object;
            }

            if (!midgard_object_class::is_multilang(get_class($object)))
            {
                return;
            }

            if ($languages = $object->get_languages())
            {
                $default_lang = self::get_default_lang();
                $real_lang = self::get_lang();

                $object_langs = array();
                $auto_has_master = false;

                foreach ($languages as $language)
                {
                    $object_langs[$language->code] = false;
                    if (!$object->get_parameter($domain, $language->code))
                    {
                        $object_langs[$language->code] = true;

                        if (   $GLOBALS['midcom_config']['multilang_auto_langs']
                            && !$auto_has_master
                            && in_array($language->code, $GLOBALS['midcom_config']['multilang_auto_langs']))
                        {
                            $auto_has_master = true;
                        }
                    }
                }

                if (   $GLOBALS['midcom_config']['multilang_auto_langs']
                    && !$auto_has_master)
                {
                    if (   !isset($object_langs[$default_lang])
                        || !$object_langs[$default_lang]
                        || (   $default_lang == ''
                            && !$GLOBALS['midcom_config']['multilang_lang0_langs'])
                       )
                    {
                        $auto_has_master = true;
                    }
                }

                foreach ($languages as $language)
                {
                    if (   $GLOBALS['midcom_config']['multilang_auto_langs']
                        && $auto_has_master
                        && in_array($language->code, $GLOBALS['midcom_config']['multilang_auto_langs']))
                    {
                        continue;
                    }

                    if (   $GLOBALS['midcom_config']['multilang_lang0_langs']
                        && $object_langs[$language->code]
                        && in_array($language->code, $GLOBALS['midcom_config']['multilang_lang0_langs']))
                    {
                        continue;
                    }

                    $object_in_lang = $object;
                    $langs = array($language->code);
                    $delete = false;

                    if (!$object_langs[$language->code])
                    {
                        if ($object_in_lang = self::get_object_in_lang($object, $language->code, true))
                        {
                            $delete = true;
                        }
                        else
                        {
                            $object_in_lang = $object;
                        }
                    } 
                    else if ($GLOBALS['midcom_config']['multilang_lang0_langs'] == array(''))
                    {
                        if ($object_in_lang = self::get_object_in_lang($object, $language->code, true))
                        {
                            array_unshift($langs, '');
                        }
                        else
                        {
                            $object_in_lang = $object;
                        }
                    }

                    if (   !$GLOBALS['midcom_config']['multilang_lang0_langs']
                        && $language->code == ''
                        && !$delete
                        && $object_in_lang = self::get_object_in_lang($object, '', true))
                    {
                        foreach ($object_langs as $lang => $translated)
                        {
                            if ($lang == $language->code) continue;

                            $object_in_langx = self::get_object_in_lang($object, $lang, true);

                            if (   $object_in_langx
                                && self::are_objects_equal($object_in_langx, $object_in_lang))
                            {
                                $delete = true;
                                break;
                            }
                        }
                    }

                    if ($delete)
                    {
                        $lang = '';
                        foreach ($object_langs as $lang => $translated)
                        {
                            if ($lang == $language->code) continue;

                            break;
                        }
                        // Not at all needed but prefer lang0 if it can be used
                        if (   $lang != ''
                            && $language->code != '')
                        {
                            if (isset($object_langs['']))
                            {
                                $lang = '';
                            }
                        }
                        if ($lang != $language->code)
                        {
                            midgard_connection::set_default_lang($lang);
                        }
                        else
                        {
                            continue;
                        }
                        midgard_connection::set_lang($language->code);

                        self::is_real(false);
                        $constraints = array
                        (
                            'domain' => $domain,
                            'name' => $language->code,
                        );
                        $object->purge_parameters($constraints);
                        $object_in_lang->delete();
                        self::is_real(true);

                        midgard_connection::set_default_lang($default_lang);
                        midgard_connection::set_lang($real_lang);
                    }
                    else
                    {
                        midgard_connection::set_lang($language->code);
                        self::sync($object_in_lang, self::langs($langs), $domain, $is_update, 'disabled');
                        midgard_connection::set_lang($real_lang);
                    }
                }
            }
        }
    }

    function set_lang_to_object_lang($object)
    {
        if (!self::is_real()) return;

        if (is_a($object, 'midcom_core_dbaobject'))
        {
            $object = $object->__object;
        }

        $lang = '';
        if (   midgard_object_class::is_multilang(get_class($object))
            && !empty($object->lang))
        {
            try
            {
                $language = new midgard_language($object->lang);
                $lang = $language->code;
            }
            catch (Exception $e)
            {
                // Nothing to do. If DB is missing the language, use lang0 then.
            }
        }

        self::set_lang_back(self::get_lang());
        return midgard_connection::set_lang($lang);
    }

    function set_default_lang_to_lang()
    {
        if (!self::is_real()) return;

        self::set_default_lang_back(self::get_default_lang());
        return midgard_connection::set_default_lang(self::get_lang());
    }

    function set_default_lang_to_lang0()
    {
        if (!self::is_real()) return;

        self::set_default_lang_back(self::get_default_lang());

        $lang = self::get_lang();
        $return = midgard_connection::set_default_lang('');
        midgard_connection::set_lang($lang);
        return $return;
    }

    function set_langs($object)
    {
        if (!self::is_real()) return;

        $set = false;

        if (   $GLOBALS['midcom_config']['multilang_lang0_langs']
            && in_array(self::get_lang(), $GLOBALS['midcom_config']['multilang_lang0_langs']))
        {
            $set = true;
        }

        else if (   $GLOBALS['midcom_config']['multilang_auto_langs']
            && in_array(self::get_lang(), $GLOBALS['midcom_config']['multilang_auto_langs']))
        {
            $set = true;
        }

        if ($set)
        {
            self::set_lang_to_object_lang($object);
            self::set_default_lang_to_lang();
        }

        self::set_langs_back($set);
    }

    function set_lang_back($new_lang = null)
    {
        if (!self::is_real()) return;

        static $lang;

        if (is_string($new_lang))
        {
            $lang = $new_lang;
        }
        else if (isset($lang))
        {
            return midgard_connection::set_lang($lang);
        }
    }

    function set_default_lang_back($new_default_lang = null)
    {
        if (!self::is_real()) return;

        static $default_lang;

        if (is_string($new_default_lang))
        {
            $default_lang = $new_default_lang;
        }
        else if (isset($default_lang))
        {
            $lang = self::get_lang();
            $return = midgard_connection::set_default_lang($default_lang);
            midgard_connection::set_lang($lang);
            return $return;
        }
    }

    function set_langs_back($new_set = null)
    {
        if (!self::is_real()) return;

        static $set = false;

        if (is_bool($new_set))
        {
            $set = $new_set;
        }
        else if ($set)
        {
            self::set_default_lang_back();
            self::set_lang_back();
        }
    }

    // $_MIDCOM free methods end.

    // These methods are for external use and can't be used in auto workflows.
    // These can e.g. use $_MIDCOM freely.

    function tree($object, $auto = true, $memory_limit = null)
    {
        $is_update = null;
        if ($auto) $is_update = true;

        $restore_memory_limit = false;
        if (!$memory_limit)
        {
            $memory_limit = ini_set('memory_limit', -1);
            $restore_memory_limit = true;
        }

        self::syncs($object, $is_update);

        if (   !empty($object->symlink)
            && (   is_a($object, 'midcom_baseclasses_database_topic')
                || is_a($object, 'midgard_topic')
               )
           )
        {
            $topic = new midcom_db_topic($object->symlink);
            if ($topic && $topic->guid)
            {
                $object = $topic;
                self::syncs($object, $is_update);
            }
            else
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Could not get target for symlinked topic #{$object->id}: " .
                    midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                debug_pop();
            }
        }

        static $reflector_loaded = false;
        if (!$reflector_loaded)
        {
            $_MIDCOM->componentloader->load('midcom.helper.reflector');
            $reflector_loaded = true;
        }

        if ($children = midcom_helper_reflector_tree::get_child_objects($object))
        {
            $children = array_reverse($children);
            while ($objects = array_pop($children))
            {
                $objects = array_reverse($objects);
                while ($object = array_pop($objects))
                {
                    self::tree($object, $auto, $memory_limit);
                }
            }
        }

        if ($restore_memory_limit)
        {
            ini_set('memory_limit', $memory_limit);
        }
    }
}
?>
