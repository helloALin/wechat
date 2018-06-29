<?php
/**
 * excel导入导出工具类
 *
 */
class ExcelUtils {

	public function __construct() {
		Vendor("PHPExcel.PHPExcel"); // 引入phpexcel类(注意你自己的路径)
		Vendor("PHPExcel.PHPExcel.IOFactory");
	}

	/**
	 * 读取excel文件
	 * @param $filename 文件名
	 * @param $encode	 编码
	 * @param $file_type 文件类型：xls,xlsx
	 * 
	 */
	public function read($filename, $encode, $file_type) {
		if (strtolower($file_type) == 'xls') {		// 判断excel表类型为2003还是2007
			Vendor("PHPExcel.PHPExcel.Reader.Excel5");
			$objReader = PHPExcel_IOFactory::createReader('Excel5');
		} elseif (strtolower($file_type) == 'xlsx') {
			Vendor("PHPExcel.PHPExcel.Reader.Excel2007");
			$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		}
		$objReader->setReadDataOnly(true);
		$objPHPExcel = $objReader->load($filename);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$highestRow = $objWorksheet->getHighestRow();
		$highestColumn = $objWorksheet->getHighestColumn();
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		$excelData = array();
		for($row = 1; $row <= $highestRow; $row ++) {
			for($col = 0; $col < $highestColumnIndex; $col ++) {
				$excelData[$row][] = (string) $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
			}
		}
		return $excelData;
	}

	public function push_other($data, $cell, $cellname, $other_data, $other_cell, $cell2, $cellname2, $other_cellname, $name = 'Excel', $title = "") {
		date_default_timezone_set('Europe/London');
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator("debug.cn@gmail.com")->setLastModifiedBy("debug.cn@gmail.com")->setTitle("数据EXCEL导出")->setSubject("数据EXCEL导出")->setDescription("数据EXCEL导出")->setKeywords("excel")->setCategory("result file");
		$num = 0;
		// 输出头部
		if ($cellname) {
			$num = 1;
			$ks_i = 0;
			$split_k = split(",", $cellname);
			foreach ( $split_k as $k_s => $v_s ) {
				$objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $ks_i) . '' . $num, $v_s);
				$ks_i = $ks_i + 1;
			}
			if ($other_cellname) {
				$split_k = split(",", $other_cellname);
				foreach ( $split_k as $k_s => $v_s ) {
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $ks_i) . '' . $num, $v_s);
					$ks_i = $ks_i + 1;
				}
			}
			if ($cellname2) {
				$split_k = split(",", $cellname2);
				foreach ( $split_k as $k_s => $v_s ) {
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $ks_i) . '' . $num, $v_s);
					$ks_i = $ks_i + 1;
				}
			}
		}
		foreach ( $data as $k => $v ) {
			$num = $num + 1;
			$ks_i = 0;
			$split_k = split(",", $cell);
			foreach ( $split_k as $k_s => $v_s ) {
				if (empty($v_s))
					continue;
				$objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $ks_i) . '' . $num, $v[$v_s]);
				$ks_i = $ks_i + 1;
			}
			
			if ($other_cell) {
				$other = $v[$other_data];
				if (! $other)
					continue;
				$otherarray = array();
				$c = unserialize($other);
				if (count($c['title'])) {
					foreach ( $c['title'] as $k_other => $v_other ) {
						if (is_array($c['sys'][$k_other])) {
							$otherarray = array_merge($otherarray, array("c_" . $c['id'][$k_other] => implode(',', $c['sys'][$k_other])));
						} else {
							$otherarray = array_merge($otherarray, array("c_" . $c['id'][$k_other] => $c['sys'][$k_other]));
						}
					}
				}
				
				$split_k = split(",", $other_cell);
				foreach ( $split_k as $k_s => $v_s ) {
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $ks_i) . '' . $num, $otherarray['c_' . $v_s]);
					$ks_i = $ks_i + 1;
				}
			}
			
			if ($cell2) {
				$split_k = split(",", $cell2);
				foreach ( $split_k as $k_s => $v_s ) {
					if (empty($v_s))
						continue;
					$objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $ks_i) . '' . $num, $v[$v_s]);
					$ks_i = $ks_i + 1;
				}
			}
		}
		$objPHPExcel->getActiveSheet()->setTitle($title);
		$objPHPExcel->setActiveSheetIndex(0);
		header('Content-Type: application/vnd.ms-excel;charset=UTF-8"');
		header('Content-Disposition: attachment;filename="' . iconv('utf-8', 'gb2312//IGNORE', $name) . '.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit();
	}

	/**
	 * 导出excel文档
	 * @param array $data 		导出数据内容，使用数组
	 * @param string $cell		导出数据内容映射的key值，使用字符串用,隔开
	 * @param string $cellname	导出数据首行的值，使用字符串用,隔开
	 * @param string $name		导出excel文件名, 默认"Excel"
	 * @param string $title		导出excel sheet名称, 默认"sheet1"
	 * @param array $colwidth	导出数据列的宽度, 默认不设置, 使用数组 array(10,20)
	 */
	public function push($data, $cell, $cellname, $name = 'Excel', $title = "", $colwidth = array()) {
		date_default_timezone_set('Europe/London');
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator("debug.cn@gmail.com")
					->setLastModifiedBy("debug.cn@gmail.com")->setTitle("数据EXCEL导出")
					->setSubject("数据EXCEL导出")->setDescription("数据EXCEL导出")
					->setKeywords("excel")->setCategory("result file");
		$num = 1;
		if ($cellname) {
			$ks_i = 0;
			$split_k = split(",", $cellname);
			foreach ( $split_k as $k_s => $v_s ) {
				$objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65 + $ks_i) . '' . $num, $v_s);
							//->getStyle()->getFont()->setBold(true);
				$objPHPExcel->setActiveSheetIndex(0)->getStyle(chr(65 + $ks_i) . '' . $num)->getFont()->setBold(true);
				if(!empty($colwidth[$k_s]))
					$objPHPExcel->getActiveSheet()->getColumnDimension(chr(65 + $ks_i))->setWidth($colwidth[$k_s]);
				$ks_i = $ks_i + 1;
			}
		}
		foreach ( $data as $k => $v ) {
			$num = $num + 1;
			$ks_i = 0;
			$split_k = split(",", $cell);
			foreach ( $split_k as $k_s => $v_s ) {
				if (empty($v_s))
					continue;
				$objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65 + $ks_i) . '' . $num, $v[$v_s]);
				$ks_i = $ks_i + 1;
			}
		}
		$objPHPExcel->getActiveSheet()->setTitle($title ? $title : 'sheet1');
		$objPHPExcel->setActiveSheetIndex(0);
		header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
		header('Content-Disposition: attachment;filename="' . iconv('utf-8', 'gb2312//IGNORE', $name) . '.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
		exit();
	}
}