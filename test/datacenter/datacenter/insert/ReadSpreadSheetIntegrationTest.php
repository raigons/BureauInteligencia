<?php
require_once '../../core/Exceptions/WrongFormatException.php';
/**
 * Description of ReadSpreadSheetIntegrationTest
 *
 * @author Ramon
 */
class ReadSpreadSheetIntegrationTest extends PHPUnit_Framework_TestCase{
    
    /**
     *
     * @var Spreadsheet_Excel_Reader 
     */
    private $spreadsheetReader;
    
    /**
     * @var ExcelInputFile 
     */
    private $inputFile;
    
    
    /**
     * @test
     */
    public function wrongFormatException(){
        $file =__DIR__."\Teste_WrongFormat.xls";        
        $this->spreadsheetReader = new Spreadsheet_Excel_Reader($file);               
        $this->setExpectedException('WrongFormatException');
        $this->inputFile = new ExcelInputFile($this->spreadsheetReader);
        $this->fail("Should have been thronw a 'WrongFormatException' cause the Spreadsheet does not have the correct format");
    }
    
    /**
     * @test
     */
    public function correctFormat(){
        $file =__DIR__."\Teste.xls";
        $this->spreadsheetReader = new Spreadsheet_Excel_Reader($file);    
        $this->inputFile = new ExcelInputFile($this->spreadsheetReader);
        $values = $this->inputFile->getYears();
        print_r($values);
        $this->assertEquals(4, sizeof($values));
        $values = $this->inputFile->getValuesFromACountry("Brasil");
        print_r($values);
    }
}

?>
