<?php
/**
 * @package    Mod_simpoll
 * @author     Rinenweb <info@rinenweb.eu>
 * @license    GNU General Public License v3
 * @link       https://www.rinenweb.eu
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Rinenweb\Module\Simpoll\Site\Helper\SimpollHelper;

$app      = Factory::getApplication();
$input    = $app->getInput();
$session  = $app->getSession();
$moduleId = (int) $module->id;

$question = (string) $params->get('question', '');

// [answer key => answer text], in the order set by the admin.
// Only these keys are accepted as votes.
$pollAnswers = SimpollHelper::getAnswers($moduleId, (array) $params->get('answers', []));

$sessionKey = 'mod_simpoll_voted_' . $moduleId;
$voted      = (bool) $session->get($sessionKey, false);

$vote       = $input->post->getAlnum('poll_answer', '');
$postedPoll = $input->post->getInt('poll_id', 0);

// Only handle a submission meant for THIS module instance
$isSubmission = !$voted && $postedPoll === $moduleId && $vote !== '';

if ($isSubmission || $voted) {
    // Moves votes stored by version 1.1 onto the answer keys (no-op once done)
    SimpollHelper::adoptLegacyVotes($moduleId, $pollAnswers);
}

if ($isSubmission) {
    if (!Session::checkToken()) {
        $app->enqueueMessage(Text::_('JINVALID_TOKEN_NOTICE'), 'warning');
    } elseif (isset($pollAnswers[$vote])) {
        SimpollHelper::recordVote($moduleId, $vote, (string) $pollAnswers[$vote]);
        $session->set($sessionKey, true);
        $voted = true;
    }
}

// Results are only needed once the visitor has voted
$counts     = [];
$totalVotes = 0;

if ($voted) {
    $counts     = SimpollHelper::getCounts($moduleId, array_keys($pollAnswers));
    $totalVotes = array_sum($counts);
}

require ModuleHelper::getLayoutPath('mod_simpoll', $params->get('layout', 'default'));
