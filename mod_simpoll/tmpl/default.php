<?php
/**
 * @package    Mod_simpoll
 * @author     Rinenweb <info@rinenweb.eu>
 * @license    GNU General Public License v3
 * @link       https://www.rinenweb.eu
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Available variables:
 * @var  object  $module       The module
 * @var  object  $params       Module parameters
 * @var  string  $question     The poll question
 * @var  array   $pollAnswers  [answer key => answer text]
 * @var  bool    $voted        Whether this visitor has voted
 * @var  array   $counts       [answer key => votes] (only when $voted)
 * @var  int     $totalVotes   Sum of $counts
 */

$customCss = trim((string) $params->get('customCss', ''));

if ($customCss !== '') {
    Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineStyle($customCss);
}

$sfx    = htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$prefix = 'simpoll-' . (int) $module->id . '-';
?>
<div class="poll-module<?php echo $sfx; ?>">
    <?php if (!$voted) : ?>
        <form method="post" action="">
            <fieldset>
                <legend class="h3"><?php echo htmlspecialchars($question, ENT_QUOTES, 'UTF-8'); ?></legend>
                <?php foreach ($pollAnswers as $key => $text) : ?>
                    <label for="<?php echo $prefix . $key; ?>">
                        <input type="radio" name="poll_answer" id="<?php echo $prefix . $key; ?>" value="<?php echo htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'); ?>" required>
                        <?php echo htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); ?>
                    </label><br>
                <?php endforeach; ?>
            </fieldset>
            <input type="hidden" name="poll_id" value="<?php echo (int) $module->id; ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button class="btn btn-secondary" type="submit"><?php echo Text::_('MOD_SIMPOLL_SUBMIT'); ?></button>
        </form>
    <?php else : ?>
        <h3><?php echo Text::_('MOD_SIMPOLL_THANK_YOU'); ?></h3>
        <h4><?php echo Text::_('MOD_SIMPOLL_RESULTS'); ?></h4>

        <?php foreach ($pollAnswers as $key => $text) :
            $count   = $counts[$key] ?? 0;
            $percent = $totalVotes > 0 ? round($count / $totalVotes * 100) : 0;
            ?>
            <div class="poll-result">
                <p>
                    <?php echo htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); ?>:
                    <?php echo Text::plural('MOD_SIMPOLL_N_VOTES', $count); ?>
                    (<?php echo $percent; ?>%)
                </p>
                <div class="poll-bar" aria-hidden="true">
                    <div class="poll-bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($params->get('custom_text')) : ?>
        <div class="poll-custom-text">
            <?php echo $params->get('custom_text'); ?>
        </div>
    <?php endif; ?>
</div>
