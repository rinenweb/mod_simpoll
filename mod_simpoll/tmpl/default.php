<?php
/**
 * @package    Mod_simpoll
 * @author     Rinenweb <info@rinenweb.eu>
 * @license    GNU General Public License v3
 * @link       https://www.rinenweb.eu
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// Check if the user has already voted
$session = Factory::getApplication()->getSession();
$voted = $session->get('mod_simpoll_voted_' . $module->id, false);
$totalVotes = 0;

// Calculate total votes from database results (if available)
if (isset($results) && !empty($results)) {
    foreach ($results as $result) {
        $totalVotes += $result->vote_count;
    }
}

// Get custom CSS from module params
$customCss = $params->get('customCss', '');
?>

<!-- Output custom CSS if any -->
<?php if (!empty($customCss)): ?>
    <style type="text/css">
        <?php echo $customCss; ?>
    </style>
<?php endif; ?>

<div class="poll-module">
    <?php if (!$voted): ?>
        <!-- Display the voting form -->
        <form method="post" action="">
            <h3><?php echo htmlspecialchars($question, ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php foreach ($answers as $key => $answer): ?>
                <?php if (isset($answer->answer_text)): ?>
                    <label>
                        <input type="radio" name="poll_answer" value="<?php echo htmlspecialchars($answer->answer_text, ENT_QUOTES, 'UTF-8'); ?>" required>
                        <?php echo htmlspecialchars($answer->answer_text, ENT_QUOTES, 'UTF-8'); ?>
                    </label><br>
                <?php endif; ?>
            <?php endforeach; ?>
            <button class="btn btn-secondary" type="submit"><?php echo Text::_('MOD_SIMPOLL_SUBMIT'); ?></button>
        </form>
    <?php else: ?>
        <!-- Display the poll results -->
        <h3><?php echo Text::_('MOD_SIMPOLL_THANK_YOU'); ?></h3>
        <h4><?php echo Text::_('MOD_SIMPOLL_RESULTS'); ?></h4>

        <?php if (isset($answers) && !empty($answers)): ?>
            <!-- Iterate over the answers defined in the module -->
            <?php foreach ($answers as $key => $answer): ?>
                <?php if (isset($answer->answer_text)): ?>
                    <?php
                        // Find the vote count for this option in the database results
                        $voteCount = 0;
                        foreach ($results as $result) {
                            if ($result->option_text === $answer->answer_text) {
                                $voteCount = $result->vote_count;
                                break;
                            }
                        }

                        // Calculate the percentage of votes
                        $percentage = $totalVotes > 0 ? round(($voteCount / $totalVotes) * 100) : 0;
                    ?>
                    <div class="poll-result">
                        <p>
                            <?php echo htmlspecialchars($answer->answer_text, ENT_QUOTES, 'UTF-8'); ?>:
                            <?php echo $voteCount; ?> <?php echo $voteCount == 1 ? Text::_('MOD_SIMPOLL_VOTE') : Text::_('MOD_SIMPOLL_VOTES'); ?>
                            (<?php echo $percentage; ?>%)
                        </p>
                        <div class="poll-bar">
                            <div class="poll-bar-fill" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

