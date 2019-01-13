<?php
/**
 * @package midcom.console
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\console\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use midgard\portable\storage\connection;
use PDO;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use midgard\portable\api\dbobject;

/**
 * Clean up repligard table
 *
 * @package midcom.console
 */
class repligard extends Command
{
    /**
     *
     * @var PDO
     */
    private $db;

    protected function configure()
    {
        $this->setName('midcom:repligard')
            ->setDescription('Clean up repligard table');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->db = connection::get_em()->getConnection()->getWrappedConnection();
        } catch (\Exception $e) {
            $this->db = $this->create_connection($input, $output);
        }

        $result = $this->_run('SELECT COUNT(guid) FROM repligard WHERE object_action=2')->fetchColumn();
        if ($result > 0) {
            $output->writeln('Found <info>' . $result . '</info> entries for purged objects');
            if ($this->_confirm($input, $output, 'Delete all rows?')) {
                $result = $this->_run('DELETE FROM repligard WHERE object_action=2', 'exec');
                $output->writeln('Deleted <comment>' . $result . '</comment> rows');
            }
        }

        $result = $this->_run('SELECT DISTINCT typename FROM repligard');
        foreach ($result->fetchAll(PDO::FETCH_COLUMN) as $typename) {
            if (!is_a($typename, dbobject::class, true)) {
                $result = $this->_run('SELECT COUNT(guid) FROM repligard WHERE typename="' . $typename . '"');
                $output->writeln('Found <info>' . $result->fetchColumn() . '</info> entries for nonexistent type <comment>' . $typename . '</comment>');
                if ($this->_confirm($input, $output, 'Delete all rows?')) {
                    $result = $this->_run('DELETE FROM repligard WHERE typename="' . $typename . '"', 'exec');
                    $output->writeln('Deleted <comment>' . $result . '</comment> rows');
                }
            }
        }
    }

    private function create_connection(InputInterface $input, OutputInterface $output)
    {
        $config = \midgard_connection::get_instance()->config;
        $defaults = array(
            'username' => $config->dbuser,
            'password' => $config->dbpass,
            'host' => $config->host,
            'dbtype' => $config->dbtype,
            'dbname' => $config->database
        );

        $dialog = $this->getHelperSet()->get('question');

        $host = $dialog->ask($input, $output, new Question('<question>DB host:</question> [' . $defaults['host'] . ']', $defaults['host']));
        $dbtype = $dialog->ask($input, $output, new Question('<question>DB type:</question> [' . $defaults['dbtype'] . ']', $defaults['dbtype']));
        $dbname = $dialog->ask($input, $output, new Question('<question>DB name:</question> [' . $defaults['dbname'] . ']', $defaults['dbname']));

        if (empty($defaults['username'])) {
            $username = $dialog->ask($input, $output, new Question('<question>DB Username:</question> '));
            $pw_question = new Question('<question>DB Password:</question> ');
            $pw_question->setHidden(true);
            $pw_question->setHiddenFallback(false);
            $password = $dialog->ask($input, $output, $pw_question);
        }

        $dsn = strtolower($dbtype) . ':host=' . $host . ';dbname=' . $dbname;
        return new PDO($dsn, $username, $password);
    }

    private function _confirm(InputInterface $input, OutputInterface $output, $question, $default = false)
    {
        $question = '<question>' . $question;
        $options = array(true => 'y', false => 'n');
        foreach ($options as $value => &$option) {
            if ($value == $default) {
                $option = strtoupper($option);
            }
        }
        $question .= ' [' . implode('|', $options). ']</question> ';
        $question = new ConfirmationQuestion($question, $default);
        $dialog = $this->getHelperSet()->get('question');
        return $dialog->ask($input, $output, $question);
    }

    private function _run($stmt, $command = 'query')
    {
        $result = $this->db->$command($stmt);
        if ($result === false) {
            throw new \RuntimeException(implode("\n", $this->db->errorInfo()));
        }
        return $result;
    }
}
