<?php
/**
 * @package midcom.grid
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\grid;

use Symfony\Component\HttpFoundation\InputBag;
use midcom_error;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Helper class for editable jqgrids
 *
 * @package midcom.grid
 */
class editor
{
    private string $operation;

    private $id;

    private array $data = [];

    public function __construct(InputBag $post, array $required_fields)
    {
        if (!$post->has('id') || !$post->has('oper')) {
            throw new midcom_error('Incomplete POST data');
        }

        $this->id = $post->get('id');
        $this->operation = $post->get('oper');

        if (!in_array($this->operation, ['edit', 'del'])) {
            throw new midcom_error('Invalid operation "' . $this->operation . '"');
        }

        foreach ($required_fields as $field) {
            if (!$post->has($field)) {
                throw new midcom_error('Incomplete POST data');
            }
            $this->data[$field] = $post->get($field);
        }
    }

    public function get_data() : array
    {
        return $this->data;
    }

    public function is_delete() : bool
    {
        return $this->operation == 'del';
    }

    public function get_id() : ?int
    {
        return str_starts_with($this->id, 'new_') ? null : (int) $this->id;
    }

    public function get_response(array $data) : JsonResponse
    {
        $data['oldid'] = $this->id;
        return new JsonResponse($data);
    }
}
