
namespace App\Libraries\%cap_module%;

use App\Libraries\System\GrantsLibrary;
use App\Models\%cap_module%\%cap_feature%Model;
class %cap_feature%Library extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $%small_feature%Model;

    function __construct()
    {
        parent::__construct();

        $this->%small_feature%Model = new %cap_feature%Model();

        $this->table = '%small_feature%';
    }


   
}