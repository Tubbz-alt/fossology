<?php
/*
Copyright (C) 2014-2018, Siemens AG

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

namespace Fossology\Decider\Test;

use Fossology\Decider\DeciderAgent;
use Fossology\Lib\BusinessRules\ClearingDecisionProcessor;
use Fossology\Lib\BusinessRules\AgentLicenseEventProcessor;
use Fossology\Lib\BusinessRules\LicenseMap;
use Fossology\Lib\Dao\AgentDao;
use Fossology\Lib\Dao\ClearingDao;
use Fossology\Lib\Dao\UploadDao;
use Fossology\Lib\Dao\HighlightDao;
use Fossology\Lib\Dao\ShowJobsDao;
use Fossology\Lib\Data\DecisionTypes;
use Fossology\Lib\Db\DbManager;
use Mockery as M;

include_once(__DIR__.'/../../../lib/php/Test/Agent/AgentTestMockHelper.php');
include_once(__DIR__.'/SchedulerTestRunner.php');

include_once(dirname(dirname(__DIR__)).'/agent/DeciderAgent.php');

class SchedulerTestRunnerMock implements SchedulerTestRunner
{
  /** @var DbManager */
  private $dbManager;
  /** @var ClearingDao */
  private $clearingDao;
  /** @var ClearingDecisionProcessor */
  private $clearingDecisionProcessor;
  /** @var AgentLicenseEventProcessor */
  private $agentLicenseEventProcessor;
  /** @var UploadDao */
  private $uploadDao;
  /** @var AgentDao */
  private $agentDao;
  /** @var DecisionTypes */
  private $decisionTypes;
  /** @var HighlightDao */
  private $highlightDao;
  /** @var ShowJobsDao */
  private $showJobsDao;

  public function __construct(DbManager $dbManager, AgentDao $agentDao, ClearingDao $clearingDao, UploadDao $uploadDao, HighlightDao $highlightDao, ShowJobsDao $showJobsDao, ClearingDecisionProcessor $clearingDecisionProcessor, AgentLicenseEventProcessor $agentLicenseEventProcessor)
  {
    $this->clearingDao = $clearingDao;
    $this->agentDao = $agentDao;
    $this->uploadDao = $uploadDao;
    $this->highlightDao = $highlightDao;
    $this->showJobsDao = $showJobsDao;
    $this->dbManager = $dbManager;
    $this->decisionTypes = new DecisionTypes();
    $this->clearingDecisionProcessor = $clearingDecisionProcessor;
    $this->agentLicenseEventProcessor = $agentLicenseEventProcessor;
  }

  /**
   * @copydoc SchedulerTestRunner::run()
   * @see SchedulerTestRunner::run()
   */
  public function run($uploadId, $userId=2, $groupId=2, $jobId=1, $args="")
  {
    $GLOBALS['userId'] = $userId;
    $GLOBALS['jobId'] = $jobId;
    $GLOBALS['groupId'] = $groupId;

    $matches = array();

    $opts = array();
    if (preg_match("/-r([0-9]*)/", $args, $matches)) {
      $opts['r'] = $matches[1];
    }

    $GLOBALS['extraOpts'] = $opts;

    $container = M::mock('Container');
    $container->shouldReceive('get')->with('db.manager')->andReturn($this->dbManager);
    $container->shouldReceive('get')->with('dao.agent')->andReturn($this->agentDao);
    $container->shouldReceive('get')->with('dao.clearing')->andReturn($this->clearingDao);
    $container->shouldReceive('get')->with('dao.upload')->andReturn($this->uploadDao);
    $container->shouldReceive('get')->with('dao.highlight')->andReturn($this->highlightDao);
    $container->shouldReceive('get')->with('dao.show_jobs')->andReturn($this->showJobsDao);
    $container->shouldReceive('get')->with('decision.types')->andReturn($this->decisionTypes);
    $container->shouldReceive('get')->with('businessrules.clearing_decision_processor')->andReturn($this->clearingDecisionProcessor);
    $container->shouldReceive('get')->with('businessrules.agent_license_event_processor')->andReturn($this->agentLicenseEventProcessor);
    $GLOBALS['container'] = $container;

    $fgetsMock = M::mock(\Fossology\Lib\Agent\FgetsMock::class);
    $fgetsMock->shouldReceive("fgets")->with(STDIN)->andReturn($uploadId, false);
    $GLOBALS['fgetsMock'] = $fgetsMock;

    $exitval = 0;

    ob_start();

    include(dirname(dirname(__DIR__)).'/agent/decider.php');

    $output = ob_get_clean();

    return array(true, $output, $exitval);
  }
}
