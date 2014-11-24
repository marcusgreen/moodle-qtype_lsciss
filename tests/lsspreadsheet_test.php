<?php


defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/type/lsspreadsheet/lib/Lsspreadsheet.php');
require_once($CFG->dirroot . '/question/type/lsspreadsheet/lib/LsspreadsheetCell.php');
require_once($CFG->dirroot . '/question/type/lsspreadsheet/lib/LsspreadsheetCellGrader.php');
require_once($CFG->dirroot . '/question/type/lsspreadsheet/lib/LsspreadsheetChart.php');
require_once($CFG->dirroot . '/question/type/lsspreadsheet/lib/LsspreadsheetChartStats.php');
require_once($CFG->dirroot . '/question/type/lsspreadsheet/phpexcel/PHPExcel.php');
require_once($CFG->dirroot . '/question/type/lsspreadsheet/tests/mocks/QaMock.php');
use Learnsci\Lsspreadsheet;


class LsspreadsheetTest extends basic_testcase {

	private $lsspreaddata;

	protected function setUp() {
		$this->spreadsheet = new Lsspreadsheet();
		$this->lsspreaddata = file_get_contents(__DIR__ . '/fixtures/sample_sheet_data.json');
		$this->lsspreaddataFermentation = file_get_contents(__DIR__ . '/fixtures/measuring_fermentation_lsspreaddata.json');
		$this->lsspreaddataBigQuestion = file_get_contents(__DIR__ . '/fixtures/big_question_lsspreaddata.json');
	}

	public function testConvertLsspreaddataJsonToObject() {
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddata);
		$spreadsheet = $this->spreadsheet->getObjectFromLsspreaddata();
	}

	public function testCreateExcelFromSpreadsheet() {
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddata);
		$spreadsheet = $this->spreadsheet->getObjectFromLsspreaddata();
		$excel = $this->spreadsheet->create_excel_marking_sheet_from_spreadsheet($spreadsheet);
	}

	public function testGetMetaData(){
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddata);

		$this->assertEquals($this->spreadsheet->numberOfColumns, 2);
		$this->assertEquals($this->spreadsheet->numberOfRows, 15);
		$this->assertEquals($this->spreadsheet->title, '');
	}

	public function testGetChartData(){
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddata);
		$result = $this->spreadsheet->getChartDataObject();
		$this->assertEquals($result, '');
	}

	public function testGetTakeTableFromLsspreaddata() {
		$options = new stdClass();
		$options->readonly = false;
		$qa = new QaMock();
		$graded = [];
		$feedbackStyles = [];
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddata);
		$tableHtml = $this->spreadsheet->getTakeTableFromLsspreaddata('', $options, $qa, $graded, $feedbackStyles);
		$expectedTableHtml = file_get_contents(__DIR__ . '/fixtures/take-table.html');
		file_put_contents('/tmp/lsspreadsheet.html', $tableHtml);
		$this->assertEquals($tableHtml, $expectedTableHtml);
	}

	public function testGradeSpreadsheetQuestion()
	{
		$responses = Array (
			'table0_cell_c1_r5' => 'male',
			'table0_cell_c1_r6' => 1,
			'table0_cell_c1_r7' => 1,
			'table0_cell_c1_r8' => 1,
			'table0_cell_c1_r9' => 0.2,
			'table0_cell_c1_r10' => 0.4);
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddata);
		$answers = $this->spreadsheet->grade_spreadsheet_question(
			$responses,
			$gradingtype = "auto");

		//table0_cell_c1_r10 should be 0.4 times table0_cell_c1_r6
		$this->assertEquals($answers['table0_cell_c1_r10']->iscorrect, true);
	}
	private function getTestResponsesFromLsspreaddata($lsspreaddata){
		$responses = [];
		$cellRefs = array_keys($this->spreadsheet->getObjectFromLsspreaddata($lsspreaddata));
		foreach ($cellRefs as $id => $cellRef) {
			$responses[$cellRef] = 1.0;
		}
		return $responses;
	}

	public function testFermentationQuestionTakeTable(){
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddataFermentation);
		$ss = $this->spreadsheet->getObjectFromLsspreaddata();

		$options = new stdClass();
		$options->readonly = false;
		$qa = new QaMock();
		$graded = [];
		$feedbackStyles = [];
		$tableHtml = $this->spreadsheet->getTakeTableFromLsspreaddata('', $options, $qa, $graded, $feedbackStyles);
	}

	public function testBigQuestionTakeTable(){
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddataBigQuestion);
		$ss = $this->spreadsheet->getObjectFromLsspreaddata();

		$options = new stdClass();
		$options->readonly = false;
		$qa = new QaMock();
		$graded = [];
		$feedbackStyles = [];
		$tableHtml = $this->spreadsheet->getTakeTableFromLsspreaddata('', $options, $qa, $graded, $feedbackStyles);
	}

	public function testGradeFermentationQuestion(){
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddataFermentation);
		$responses = $this->getTestResponsesFromLsspreaddata($this->lsspreaddataFermentation);
		$answers = $this->spreadsheet->grade_spreadsheet_question(
		$responses,
		$gradingtype = "auto");
	}

	public function testGradeBigQuestion(){
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddataFermentation);
		$responses = $this->getTestResponsesFromLsspreaddata($this->lsspreaddataBigQuestion);
		$answers = $this->spreadsheet->grade_spreadsheet_question(
		$responses,
		$gradingtype = "auto");
	}

	public function testGetCellCorrectness(){

		$submitted_answer = 4;
		$calcAnswer = 4;
		$cell_rangetype = 'SigfigRange';
		$cell_rangeval = '2';
		$answer = $this->spreadsheet->get_cell_correctness($submitted_answer, $calcAnswer, $cell_rangetype, $cell_rangeval);
	}

	public function test_get_field_names(){
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddata);
		$expected = array (
			'table0_cell_c1_r10',
	    'table0_cell_c1_r5',
	    'table0_cell_c1_r6',
	    'table0_cell_c1_r7',
	    'table0_cell_c1_r8',
	    'table0_cell_c1_r9'
    );
		$result = $this->spreadsheet->get_field_names();
		$this->assertEquals($expected, $result);
	}

	public function test_get_fractional_grade(){
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddata);
		$responses = Array (
			'table0_cell_c1_r5' => 'male0',
			'table0_cell_c1_r6' => 1,
			'table0_cell_c1_r7' => 1,
			'table0_cell_c1_r8' => 1,
			'table0_cell_c1_r9' => 0.2,
			'table0_cell_c1_r10' => 0.4);
		$result = $this->spreadsheet->get_fractional_grade($responses);
		$this->assertEquals($result, 1);
	}

	public function testMethodMarkCell(){
		$responses = Array (
			'table0_cell_c1_r5' => 89,
			'table0_cell_c1_r6' => 10,
			'table0_cell_c1_r7' => 3,
			'table0_cell_c1_r8' => 5,
			'table0_cell_c1_r9' => 6,
			'table0_cell_c1_r10' => 1);
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddata);


		$cell_rangetype = 'SigfigRange';
		$cell_rangeval = '2';
		$cell_excelref = 'B11';
		$cell_formula = '=0.4 * B7';
		$submitted_answer = 4;
		$moodleinput_excel = $this->spreadsheet->create_excel_populated_all_moodle_inputs($responses, false);
		$methodAnswer = $this->spreadsheet->method_mark_cell($moodleinput_excel, $cell_excelref, $cell_formula, $cell_rangetype, $cell_rangeval, $submitted_answer);
	}

	public function testMethodMarkCellAgreesWithCellCorrectness(){
		$responses = Array (
			'table0_cell_c1_r5' => 89,
			'table0_cell_c1_r6' => 10,
			'table0_cell_c1_r7' => 3,
			'table0_cell_c1_r8' => 5,
			'table0_cell_c1_r9' => 6,
			'table0_cell_c1_r10' => 1);
		$this->spreadsheet->setJsonStringFromDb($this->lsspreaddata);

		$cell_rangetype = 'SigfigRange';
		$cell_rangeval = '2';
		$cell_excelref = 'B11';
		$cell_formula = '=0.4 * B7';
		$submitted_answer = 4;
		$moodleinput_excel = $this->spreadsheet->create_excel_populated_all_moodle_inputs($responses, false);
		$methodAnswer = $this->spreadsheet->method_mark_cell($moodleinput_excel, $cell_excelref, $cell_formula, $cell_rangetype, $cell_rangeval, $submitted_answer);
	}

}
