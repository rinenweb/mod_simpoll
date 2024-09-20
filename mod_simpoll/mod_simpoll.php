<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;  // Use the Text class to handle language strings

// Get the parameters from the module
$module_id = $module->id;  // Automatically fetch the module's unique id (poll_id)
$question = $params->get('question');
$answers = $params->get('answers', []);

// Initialize the Joomla database object
$db = Factory::getDbo();
$query = $db->getQuery(true);

$input = Factory::getApplication()->input;
$vote = $input->post->get('poll_answer', '', 'STRING');

// Store vote using session (to prevent multiple votes)
$session = Factory::getApplication()->getSession();
$voted = $session->get('mod_simpoll_voted_' . $module_id, false);

// If the user hasn't voted and submits a vote, process the vote
if ($vote && !$voted) {
    $session->set('mod_simpoll_voted_' . $module_id, true);

    // Increment the vote count in the database
    $query->clear()
        ->select('vote_count')
        ->from($db->quoteName('#__simpoll_votes'))
        ->where($db->quoteName('poll_id') . ' = ' . (int)$module_id)
        ->where($db->quoteName('option_text') . ' = ' . $db->quote($vote));

    $db->setQuery($query);
    $currentVoteCount = $db->loadResult();

    if ($currentVoteCount !== null) {
        $query->clear()
            ->update($db->quoteName('#__simpoll_votes'))
            ->set($db->quoteName('vote_count') . ' = ' . (int)($currentVoteCount + 1))
            ->where($db->quoteName('poll_id') . ' = ' . (int)$module_id)
            ->where($db->quoteName('option_text') . ' = ' . $db->quote($vote));
    } else {
        $query->clear()
            ->insert($db->quoteName('#__simpoll_votes'))
            ->columns($db->quoteName(['poll_id', 'option_text', 'vote_count']))
            ->values((int)$module_id . ', ' . $db->quote($vote) . ', 1');
    }

    $db->setQuery($query);
    $db->execute();
}

// Fetch vote results specific to this poll/module
$query->clear()
    ->select($db->quoteName(['option_text', 'vote_count']))
    ->from($db->quoteName('#__simpoll_votes'))
    ->where($db->quoteName('poll_id') . ' = ' . (int)$module_id);

$db->setQuery($query);
$results = $db->loadObjectList();

// Display the poll form or results
require ModuleHelper::getLayoutPath('mod_simpoll', $params->get('layout', 'default'));

