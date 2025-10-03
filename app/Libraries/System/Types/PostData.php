<?php 

namespace App\Libraries\System\Types;

/**
 * Class PostData - Data Transfer Object (DTO)
 *
 * Represents structured post data with a required header and an optional detail section.
 *
 * Usage:
 *   $postData = new PostData([
 *       'header' => [...],      // Required associative array
 *       'detail' => [...]       // Optional associative array
 *   ]);
 *
 * Properties:
 * @property array $header   The main header data. Must be provided in the constructor.
 * @property array|null $detail  Optional detail data. Can be null if not provided.
 *
 * Constructor:
 * @param array $data  Associative array with at least a 'header' key. Optionally includes 'detail'.
 * @throws \InvalidArgumentException if 'header' key is missing in $data.
 */

class PostData {
    public array $header;
    public ?array $detail; // The ? makes the property nullable
    public function __construct(array $data, public string $tableName = '', public bool $returnAsJsonResponseInterface = true) {
        $this->header = $data['header'] ?? $data;
        $this->detail = $data['detail'] ?? null;
    }
}