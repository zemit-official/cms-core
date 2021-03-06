<?php
/**
 * This file is part of the Zemit Framework.
 *
 * (c) Zemit Team <contact@zemit.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Zemit\Modules\Frontend\Controllers;

use Zemit\Mvc\Controller\StatusCode;

/**
 * Class ErrorController
 *
 * @author Julien Turbide <jturbide@nuagerie.com>
 * @copyright Zemit Team <contact@zemit.com>
 *
 * @since 1.0
 * @version 1.0
 *
 * @package Zemit\Modules\Frontend\Controllers
 */
class ErrorController extends AbstractController
{
    use StatusCode;
    
    public function initialize()
    {
        $this->view->pick('error/index');
        parent::initialize();
    }
}
