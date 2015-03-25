<?php
/*
 Copyright (C) 2014-2015 Siemens AG

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 version 2 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

use Fossology\Lib\Auth\Auth;
use Fossology\Lib\Dao\UploadDao;
use Fossology\Lib\Data\Upload\Upload;
use Fossology\Lib\Plugin\DefaultPlugin;
use Symfony\Component\HttpFoundation\Request;

class ReadmeossGenerator extends DefaultPlugin
{
  const NAME = 'agent_foreadmeoss';
  
  function __construct()
  {
    parent::__construct(self::NAME, array(
        self::TITLE => _("ReadME_OSS generation"),
        self::PERMISSION => Auth::PERM_WRITE,
        self::REQUIRES_LOGIN => TRUE
    ));
  }

  function preInstall()
  {
    $text = _("Generate ReadMe_OSS");
    menu_insert("Browse-Pfile::Generate&nbsp;ReadMe_OSS", 0, self::NAME, $text);
    
  }

  protected function handle(Request $request)
  {
    global $SysConf;
    $userId = $SysConf['auth'][Auth::USER_ID];
    $groupId = $SysConf['auth'][Auth::GROUP_ID];

    $uploadId = intval($request->get('upload'));
    if ($uploadId <=0)
    {
      $vars = array("content"=>_("parameter error"));
      return $this->render('include/base.html.twig', $this->mergeWithDefault($vars));
    }
    /** @var UploadDao */
    $uploadDao = $GLOBALS['container']->get('dao.upload');
    if (!$uploadDao->isAccessible($uploadId, $groupId))
    {
      $vars = array("content"=>_("permission denied"));
      return $this->render('include/base.html.twig', $this->mergeWithDefault($vars));
    }

    /** @var Upload */
    $upload = $uploadDao->getUpload($uploadId);
    if ($upload === null)
    {
      $vars = array("content"=>_("cannot find upload"));
      return $this->render('include/base.html.twig', $this->mergeWithDefault($vars));
    }
    
    $readMeOssAgent = plugin_find('agent_readmeoss');
    $jobId = JobAddJob($userId, $groupId, $upload->getFilename(), $uploadId);
    $error = "";
    $jobQueueId = $readMeOssAgent->AgentAdd($jobId, $uploadId, $error, array());

    if ($jobQueueId<0)
    {
      $vars = array("content"=>_("Cannot schedule").": ".$error);
      return $this->render('include/base.html.twig', $this->mergeWithDefault($vars));
    }

    $vars = array('jqPk' => $jobQueueId,
                  'downloadLink' => Traceback_uri(). "?mod=download&report=".$jobId,
                  'reportType' => "ReadMe_OSS");
    $text = sprintf(_("Generating ReadMe_OSS for '%s'"), $upload->getFilename());
    $vars['content'] = "<h2>".$text."</h2>";
    return $this->render("report.html.twig",$this->mergeWithDefault($vars));
  }
}

register_plugin(new ReadmeossGenerator());
