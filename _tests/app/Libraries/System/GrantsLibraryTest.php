<?php 

namespace Tests\Support\Libraries\Core;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\System\GrantsLibrary;
// use CodeIgniter\Test\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Depends;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\ContextDefinitionSeeder;

class GrantsLibraryTest extends CIUnitTestCase {

    // use ControllerTestTrait;
    use DatabaseTestTrait;
    private $grantsLibrary;
    protected $seed = ContextDefinitionSeeder::class;

    function setUp(): void{
        parent::setUp();
        $this->grantsLibrary = new GrantsLibrary();
    }

    function testdefaultUnsetColumnsIsAbleToUnsetNonContigousColumns(){

        $columns = ['firstColumn', 'secondColumn', 'thirdColumn', 'fourthColumn', 'fifthColumn'];
        
        $remainingColumns = $this->grantsLibrary->defaultUnsetColumns($columns, ['secondColumn','fifthColumn']);
        
        $this->assertEquals(['firstColumn', 'thirdColumn', 'fourthColumn'], $remainingColumns);
        $this->assertEquals(['firstColumn', 'thirdColumn', 'fourthColumn'], $columns);
        $this->assertCount(3, $columns);
    }

    #[Depends('testDeriveLookupTablesReturnsAllLookupTablesForValidTableName')]
    function testLookUpTableFieldNotEmpty($deriveLookupTables){

        $cancelChequesLibrary = new \App\Libraries\Grants\CancelChequeLibrary();

        $lookupTableColumns = $this->grantsLibrary->lookupTablesFields($cancelChequesLibrary, $deriveLookupTables);

        $this->assertIsArray($lookupTableColumns);
        $this->assertNotEmpty($lookupTableColumns);

        $this->assertTrue(in_array('item_reason_id', $lookupTableColumns));
        $this->assertTrue(in_array('cheque_book_id', $lookupTableColumns));
        $this->assertTrue(in_array('voucher_id', $lookupTableColumns));
    }

    public function testDeriveLookupTablesReturnsAllLookupTablesForValidTableName(): array 
    {
        $tableName = 'cancel_cheque';
        
        // $approveItemLibrary = $this->createMock(\App\Libraries\Core\ApproveItemLibrary::class);
        // $approveItemLibrary = $this->createStub(\App\Libraries\Core\ApproveItemLibrary::class);
        // $approveItemLibrary->method('approveableItem')->willReturn(false);


        $lookupTables = $this->grantsLibrary->deriveLookupTables($tableName);

        $this->assertIsArray($lookupTables);
        $this->assertNotEmpty($lookupTables);
        $this->assertTrue(in_array('item_reason', $lookupTables));
        $this->assertTrue(in_array('cheque_book', $lookupTables));
        $this->assertTrue(in_array('voucher', $lookupTables));

        return $lookupTables;
    }

    function setClassProtectedPropertyValue($classPropertyName, $propertyValue){
        // Use Reflection to access the protected property
        $reflection = new \ReflectionClass($this->grantsLibrary);
        $property = $reflection->getProperty($classPropertyName);
        $property->setAccessible(true); // Make the property accessible

        // Set the value of the protected property
        $property->setValue($this->grantsLibrary, $propertyValue);
    }

    public function testDeriveLookupTablesReturnsAllLookupTablesEmptyTableName(): void
    {

        $tableName = 'cancel_cheque';
        $this->setClassProtectedPropertyValue('controller', $tableName);   
        $lookupTables = $this->grantsLibrary->deriveLookupTables();


        $this->assertIsArray($lookupTables);
        $this->assertNotEmpty($lookupTables);
        $this->assertTrue(in_array('item_reason', $lookupTables));
        $this->assertTrue(in_array('cheque_book', $lookupTables));
        $this->assertTrue(in_array('voucher', $lookupTables));
    }

    function testFeatureListTableVisibleColumnsReturnsMainListTableColumns(){
        $tableName = 'cancel_cheque';
        // $this->setClassProtectedPropertyValue('controller', $tableName);   
        $this->setPrivateProperty($this->grantsLibrary, 'controller', $tableName);
        $listTableVisibleColumns = $this->grantsLibrary->featureListTableVisibleColumns();
        
        $this->assertIsArray($listTableVisibleColumns);
        $this->assertTrue(in_array('cancel_cheque_id', $listTableVisibleColumns));
    }

    function testFeatureListTableVisibleColumnsReturnsDetailsListTableColumns(){
        $tableName = 'office_bank';
        // $this->setClassProtectedPropertyValue('controller', $tableName);   
        $this->setPrivateProperty($this->grantsLibrary, 'controller', $tableName);
        $listTableVisibleColumns = $this->grantsLibrary->featureListTableVisibleColumns('bank');
        
        $this->assertIsArray($listTableVisibleColumns);
        $this->assertTrue(in_array('office_bank_id', $listTableVisibleColumns));
    }

    function testGetListColumnsReturnsArrayWithoutFeatureLibraryListMethod(){
        $tableName = 'cancel_cheque';
        // $this->setClassProtectedPropertyValue('controller', $tableName);   
        $this->setPrivateProperty($this->grantsLibrary, 'controller', $tableName);
        $listTableVisibleColumns = $this->grantsLibrary->getListColumns();
        
        $this->assertIsArray($listTableVisibleColumns);
        $this->assertTrue(in_array('cancel_cheque_id', $listTableVisibleColumns));
    }

    function testGetListColumnsReturnsArrayWithFeatureLibraryListMethod(){
        $tableName = 'office';

        // $this->setClassProtectedPropertyValue('controller', $tableName);   
        $this->setPrivateProperty($this->grantsLibrary, 'controller', $tableName);
        $listTableVisibleColumns = $this->grantsLibrary->getListColumns();

        
        $this->assertIsArray($listTableVisibleColumns);
        $this->assertTrue(in_array($tableName.'_id', $listTableVisibleColumns));
    }

    function testCallingPrivateMethodTablesWithAccountSystemRelationship(){
        $result = $this->grantsLibrary->call('grants.tablesWithAccountSystemRelationship');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertTrue(in_array('office', $result));
        $this->assertTrue(in_array('bank', $result));
    }

    function testCallingPrivateMethodTablesWithAccountSystemRelationshipWithSingleNamespace(){
        $result = $this->grantsLibrary->call('tablesWithAccountSystemRelationship');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertTrue(in_array('office', $result));
        $this->assertTrue(in_array('bank', $result));
    }

}