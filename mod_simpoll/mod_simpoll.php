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

// Store vote using session (temporary)
$session = Factory::getApplication()->getSession();
$voted = $session->get('mod_simpoll_voted', false);

if ($vote && !$voted) {
    // Mark as voted
    $session->set('mod_simpoll_voted', true);

    // Increment the result for the selected answer in the back-end
    foreach ($answers as $key => $answer) {
        if ($answer->example_text === $vote) {
            // Increment the vote count in the back-end
            $answers[$key]->vote_count++;
        }
    }

    // Save the updated vote counts back to the module params
    $params->set('answers', $answers);
}

// Display the poll form or results
require ModuleHelper::getLayoutPath('mod_simpoll', $params->get('layout', 'default'));

