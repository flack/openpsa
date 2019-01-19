<?php
/**
 * @package midcom.console
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use midcom;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

/**
 * CLI login helper
 *
 * @package midcom.console
 */
trait loginhelper
{
    public function login(QuestionHelper $dialog, InputInterface $input, OutputInterface $output)
    {
        $user_question = new Question('<question>Username:</question> ');
        $pw_question = new Question('<question>Password:</question> ');
        $pw_question->setHidden(true);
        $pw_question->setHiddenFallback(false);

        do {
            $username = $dialog->ask($input, $output, $user_question);
            $password = $dialog->ask($input, $output, $pw_question);
            if (!midcom::get()->auth->login($username, $password)) {
                $output->writeln('Login failed');
            }
        } while (!midcom::get()->auth->is_valid_user());
    }

    public function require_admin(QuestionHelper $dialog, InputInterface $input, OutputInterface $output)
    {
        $this->login($dialog, $input, $output);

        midcom::get()->auth->require_admin_user();
    }
}
