<?php
/**
 * @package    Mod_simpoll
 * @author     Rinenweb <info@rinenweb.eu>
 * @license    GNU General Public License v3
 * @link       https://www.rinenweb.eu
 */

namespace Rinenweb\Module\Simpoll\Site\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Rinenweb\Module\Simpoll\Site\Helper\SimpollHelper;

/**
 * Answer row field: stores the answer's stable id in a hidden input
 * and shows the votes the answer has received so far (read-only).
 */
class SimpollanswerField extends FormField
{
    /**
     * @var  string
     */
    protected $type = 'Simpollanswer';

    /**
     * @var  bool
     */
    private static $scriptAdded = false;

    /**
     * @return  string
     */
    protected function getInput()
    {
        // The module being edited (0 for a new module)
        $pollId = Factory::getApplication()->getInput()->getInt('id', 0);
        $text   = (string) $this->form->getValue('answer_text', null, '');
        $key    = (string) preg_replace('/[^a-zA-Z0-9]/', '', (string) $this->value);
        $votes  = 0;

        if (trim($text) !== '') {
            // Answers saved with 1.1 get the same derived key the front end uses;
            // it is stored on the next save, so it stays put when the text is edited.
            $key = SimpollHelper::answerKey($pollId, $key, $text);

            if ($pollId > 0) {
                SimpollHelper::adoptLegacyVotes($pollId, [$key => $text]);
                $votes = SimpollHelper::getCounts($pollId, [$key])[$key] ?? 0;
            }
        }

        $this->addScript();

        return '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '"'
            . ' value="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" class="simpoll-answer-id">'
            . '<span class="badge bg-secondary simpoll-answer-votes">'
            . Text::plural('MOD_SIMPOLL_N_VOTES', $votes)
            . '</span>';
    }

    /**
     * New rows are cloned in the browser from an empty template row, so their id is created here:
     * every empty or duplicated id input gets a fresh random one.
     */
    private function addScript(): void
    {
        if (self::$scriptAdded) {
            return;
        }

        self::$scriptAdded = true;

        $js = <<<'JS'
(function () {
    function newId() {
        var bytes = new Uint8Array(6);
        window.crypto.getRandomValues(bytes);
        return Array.prototype.map.call(bytes, function (b) {
            return ('0' + b.toString(16)).slice(-2);
        }).join('');
    }
    function fillIds() {
        var seen = {};
        document.querySelectorAll('input.simpoll-answer-id').forEach(function (input) {
            if (!input.value || seen[input.value]) {
                input.value = newId();
            }
            seen[input.value] = true;
        });
    }
    document.addEventListener('DOMContentLoaded', function () {
        fillIds();
        document.addEventListener('subform-row-add', fillIds);
        new MutationObserver(fillIds).observe(document.body, { childList: true, subtree: true });
    });
})();
JS;

        Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript($js);
    }
}
