<?php
/**
 * @package    Mod_simpoll
 * @author     Rinenweb <info@rinenweb.eu>
 * @license    GNU General Public License v3
 * @link       https://www.rinenweb.eu
 */

namespace Rinenweb\Module\Simpoll\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Shared vote logic for the module output and the admin "Votes" field.
 *
 * Every answer is identified by a stable key (its answer_id) instead of its text,
 * so editing an answer's text keeps its votes.
 */
abstract class SimpollHelper
{
    /**
     * The table holding vote counts.
     */
    private const TABLE = '#__simpoll_votes';

    /**
     * Returns the database driver (works on Joomla 4.0+).
     */
    public static function getDb(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    /**
     * New query object: createQuery() where available (Joomla 5+), getQuery(true) on Joomla 4.
     *
     * @return  \Joomla\Database\DatabaseQuery
     */
    private static function newQuery(DatabaseInterface $db)
    {
        return method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true);
    }

    /**
     * Stable key of an answer.
     *
     * Answers saved with 1.2.0+ have an answer_id. Answers saved with 1.1 don't, so a key is
     * derived from the module id and the text. The admin field derives the same key and
     * writes it into the answer on the next save, so it never changes after that.
     */
    public static function answerKey(int $pollId, string $answerId, string $text): string
    {
        $answerId = (string) preg_replace('/[^a-zA-Z0-9]/', '', $answerId);

        if ($answerId !== '') {
            return $answerId;
        }

        return trim($text) === '' ? '' : self::derivedKey($pollId, $text);
    }

    /**
     * Key derived from the module id and the answer text.
     */
    private static function derivedKey(int $pollId, string $text): string
    {
        return substr(md5($pollId . ':' . $text), 0, 12);
    }

    /**
     * Turns the "answers" subform value into an ordered [key => text] list,
     * skipping empty rows.
     *
     * @param   int    $pollId  The module id.
     * @param   array  $rows    The subform rows from the module params.
     *
     * @return  array
     */
    public static function getAnswers(int $pollId, array $rows): array
    {
        $answers = [];

        foreach ($rows as $row) {
            $row  = (object) $row;
            $text = isset($row->answer_text) ? (string) $row->answer_text : '';

            if (trim($text) === '') {
                continue;
            }

            $key = self::answerKey($pollId, isset($row->answer_id) ? (string) $row->answer_id : '', $text);

            // Safety net for a duplicated id: fall back to a text-based key
            if (isset($answers[$key])) {
                $key = self::derivedKey($pollId, $text);

                if (isset($answers[$key])) {
                    continue;
                }
            }

            $answers[$key] = $text;
        }

        return $answers;
    }

    /**
     * Assigns answer keys to vote rows stored by version 1.1 (which only stored the text).
     * Runs one cheap query and only writes when such rows still exist.
     *
     * @param   int    $pollId   The module id.
     * @param   array  $answers  [key => text] of the current answers.
     */
    public static function adoptLegacyVotes(int $pollId, array $answers): void
    {
        if ($pollId <= 0 || !$answers) {
            return;
        }

        $db    = self::getDb();
        $query = self::newQuery($db)
            ->select($db->quoteName(['id', 'option_text']))
            ->from($db->quoteName(self::TABLE))
            ->where($db->quoteName('poll_id') . ' = :pollid')
            ->where($db->quoteName('answer_id') . ' = ' . $db->quote(''))
            ->bind(':pollid', $pollId, ParameterType::INTEGER);

        $legacyRows = $db->setQuery($query)->loadObjectList();

        if (!$legacyRows) {
            return;
        }

        $keyByText = [];

        foreach ($answers as $key => $text) {
            $keyByText[(string) $text] = (string) $key;
        }

        foreach ($legacyRows as $row) {
            if (!isset($keyByText[$row->option_text])) {
                continue;
            }

            $key   = $keyByText[$row->option_text];
            $rowId = (int) $row->id;
            $query = self::newQuery($db)
                ->update($db->quoteName(self::TABLE))
                ->set($db->quoteName('answer_id') . ' = :answerid')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':answerid', $key)
                ->bind(':id', $rowId, ParameterType::INTEGER);

            $db->setQuery($query)->execute();
        }
    }

    /**
     * Adds one vote to an answer (atomic increment, insert on first vote).
     *
     * @param   int     $pollId  The module id.
     * @param   string  $key     The answer key.
     * @param   string  $text    The current answer text, kept as a readable label in the table.
     */
    public static function recordVote(int $pollId, string $key, string $text): void
    {
        $db    = self::getDb();
        $query = self::newQuery($db)
            ->update($db->quoteName(self::TABLE))
            ->set($db->quoteName('vote_count') . ' = ' . $db->quoteName('vote_count') . ' + 1')
            ->set($db->quoteName('option_text') . ' = :option')
            ->where($db->quoteName('poll_id') . ' = :pollid')
            ->where($db->quoteName('answer_id') . ' = :answerid')
            ->bind(':option', $text)
            ->bind(':pollid', $pollId, ParameterType::INTEGER)
            ->bind(':answerid', $key);

        $db->setQuery($query)->execute();

        if ($db->getAffectedRows() > 0) {
            return;
        }

        $query = self::newQuery($db)
            ->insert($db->quoteName(self::TABLE))
            ->columns($db->quoteName(['poll_id', 'answer_id', 'option_text', 'vote_count']))
            ->values(':pollid, :answerid, :option, 1')
            ->bind(':pollid', $pollId, ParameterType::INTEGER)
            ->bind(':answerid', $key)
            ->bind(':option', $text);

        $db->setQuery($query)->execute();
    }

    /**
     * Vote counts for the given answer keys, in the same order. Votes of deleted answers are ignored.
     *
     * @param   int    $pollId  The module id.
     * @param   array  $keys    Answer keys.
     *
     * @return  array  [key => count]
     */
    public static function getCounts(int $pollId, array $keys): array
    {
        $counts = array_fill_keys($keys, 0);

        if ($pollId <= 0 || !$keys) {
            return $counts;
        }

        $db    = self::getDb();
        $query = self::newQuery($db)
            ->select($db->quoteName('answer_id'))
            ->select('SUM(' . $db->quoteName('vote_count') . ') AS ' . $db->quoteName('votes'))
            ->from($db->quoteName(self::TABLE))
            ->where($db->quoteName('poll_id') . ' = :pollid')
            ->where($db->quoteName('answer_id') . ' <> ' . $db->quote(''))
            ->group($db->quoteName('answer_id'))
            ->bind(':pollid', $pollId, ParameterType::INTEGER);

        foreach ($db->setQuery($query)->loadObjectList() as $row) {
            if (array_key_exists($row->answer_id, $counts)) {
                $counts[$row->answer_id] += (int) $row->votes;
            }
        }

        return $counts;
    }
}
