<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ModuleHelper;

// Get the parameters from the module
$question = $params->get('question');
$answers = $params->get('answers', []);

// Check if the form has been submitted
$input = Factory::getApplication()->input;
$vote = $input->post->get('poll_answer', '', 'STRING');
// $reset = $input->post->get('reset_vote', '', 'STRING'); // Check if reset is requested

// Store vote using session (temporary)
$session = Factory::getApplication()->getSession();
$voted = $session->get('mod_simpoll_voted', false);

// Handle reset vote
//if ($reset) {
    // Clear the session so the user can vote again
//    $session->clear('mod_simpoll_voted');
//    $voted = false; // Reset the voted flag
//}

// Process vote if it's not a reset and the user hasn't already voted
if ($vote && !$voted) {
    // Mark as voted
    $session->set('mod_simpoll_voted', true);

    // Increment the result for the selected answer in the back-end
    foreach ($answers as $answer) {
        if ($answer->answer_text === $vote) {
            // Increment the vote count
            $answer->vote_count++;
        }
    }

    // Save the updated vote counts back to the module params
    $params->set('answers', $answers);
}

// Display the poll form or results
require ModuleHelper::getLayoutPath('mod_simpoll', $params->get('layout', 'default'));
