<?php
/**
 * @author jlchassaing <jlchassaing@gmail.com>
 * @licence MIT
 */

namespace Gie\EzToolbar\Menu\Event;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent as BaseConfigureMenuEvent;

class ConfigureMenuEvent extends BaseConfigureMenuEvent
{
    const CONTENT_TOOLBAR_MENU = 'gie_ez_toolbar.content.configure.toolbar_menu';

}