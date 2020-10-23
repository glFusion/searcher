<?php
/**
 * Class to provide admin and user-facing menus.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     searcher
 * @version     v1.0.0
 * @since       v1.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Searcher;


/**
 * Class to provide admin and user-facing menus.
 * @package searcher
 */
class Menu
{

    /**
     * Create the Administrative menu.
     *
     * @param   string  $view       Current view
     * @return  string      HTML for menu
     */
    public static function Admin($view='')
    {
        global $_CONF, $LANG_ADMIN, $LANG_SRCH, $LANG_SRCH_ADM, $_SRCH_CONF;
        USES_lib_admin();

        $retval = '';

        $T = new \Template(SRCH_PI_PATH . '/templates');
        $T->set_file('admin', 'admin_header.thtml');

        $token = SEC_createToken();

        $menu_arr = array(
            array(
                'url'   => $_CONF['site_admin_url'].'/plugins/searcher/index.php',
                'text'  => $LANG_SRCH_ADM['searcher_admin'],
                'active' => $view == 'counters' ? true : false,
            ),
            array(
                'url'   => $_CONF['site_admin_url'].'/plugins/searcher/reindex.php',
                'text'  => $LANG_SRCH_ADM['reindex_title'],
                'active' => $view == 'reindex' ? true : false,
            ),
            array(
                'url'   => $_CONF['site_admin_url'].'/plugins/searcher/index.php?chgweights=x',
                'text'  => $LANG_SRCH_ADM['chg_weights'],
                'active' => $view == 'chgweights' ? true : false,
            ),
            array(
                'url'   => $_CONF['site_admin_url'],
                'text'  => $LANG_ADMIN['admin_home']
            ),
        );
        $explanation =  $LANG_SRCH['hlp_' . $view];

        $T->set_var(
            'start_block',
            COM_startBlock(
                $LANG_SRCH_ADM['searcher_admin'] .' (v'.$_SRCH_CONF['pi_version'].')', '',
                COM_getBlockTemplate('_admin_block', 'header')
            )
        );
        $T->set_var(
            'admin_menu',
            ADMIN_createMenu(
                $menu_arr,
                $explanation,
                plugin_geticon_searcher()
            )
        );
        $T->set_var(
            'end_block',
            COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'))
        );
        $T->parse('output', 'admin');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }

}

?>
