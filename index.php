<?
error_reporting(E_ALL ^ E_NOTICE);
echo '<pre>';

$dom = DOMDocument::load( 'tests.xml' );
$Worksheets = $dom->getElementsByTagName( 'Worksheet' );
foreach($Worksheets as $Worksheet) {
	$rows = $Worksheet->getElementsByTagName( 'Row' );
	$row_index = 1;
	$sheetname = $Worksheet->getAttribute( 'ss:Name' );
	foreach ($rows as $row)	{
		
		$rind = $row->getAttribute( 'ss:Index' );
		if ( $rind != null ) {
			$row_index = $rind;
		}
		
		$cells = $row->getElementsByTagName( 'Cell' );
		$index = 1;
		foreach( $cells as $cell ) {
			
			$cind = $cell->getAttribute( 'ss:Index' );
			if ( $cind != null ) {
				$index = $ind;
			}

			$formula = trim($cell->getAttribute( 'ss:Formula' ), ' =');

			$xd =  array( 
			'value' => $cell->nodeValue,
			'formula' => $formula,
			);
			if( strlen( $xd['value'] ) || strlen( $xd['formula'] ) ) {
				$spreadsheet_data[$sheetname][ $row_index ][ $index ] = $xd;
			}
			
			$index += 1;
		}

		$row_index++;
	}
}

foreach( $spreadsheet_data as $sheetname => &$sheet ) {

	foreach( $sheet as $row_index => &$column ) {

		foreach( $column as $col_index => &$col_value ) {
			if( !strlen( $col_value['expanded'] ) ){
				if( strlen($col_value['formula']) ) {
					$col_value['expanded']=  expand_eq( $col_value['formula'], $row_index, $col_index, $sheetname );
				}else{
					//$col_value['expanded'] = " #{$sheetname}_{$row_index}_{$col_index}# ";
				}
			}

			if( $col_value['expanded'] ) {
				echo '<strong>Forumla '. $sheetname . ':' . base_xls($col_index) . '' . $row_index .'</strong>:' . $col_value['expanded'] . ':' . $col_value['formula'] . '<br />';
				echo '<strong>Returns</strong>: ' . eval( 'return ' . $col_value['expanded'] . ';' ) . '<br /><br />';
				//die('dead');
				
				//echo $Inputs_12_2 . ' ' .  $Rails_5_3 . '<br />';
				
				flush();
			}

		}
	}
}

function XML_XLS_IF( $bool, $a, $b = 0 ) {
	if( $bool ) {
		return $a;
	}else{
		return $b;	
	}
}

function XML_XLS_MAX() {
	return max( func_get_args() );
}

function XML_XLS_MIN() {
	return min( func_get_args() );
}

function XML_XLS_OR( $a, $b ) {
	return $a || $b;	
}

function XML_XLS_AND( $a, $b ) {
	return $a && $b;
}

function XML_XLS_CONCATENATE() {
	$j = '';
	for ($i = 0;$i < func_num_args();$i++) {
		$j .= func_get_arg($i);
	}
	return $j;
}

function XML_XLS_MID( $text, $start, $end ) {
	return substr( $text, $start - 1, $end );
}

function XML_XLS_ISEVEN( $x ) {
	return !( $x & 1 );
}



function expand_eq( $formula, $row_index, $col_index, $sheet ) {
	//$expanded_formula = $formula;
	global $spreadsheet_data;


	//LITTERAL REPLACMENT / EXPANSION
	$expanded_formula = preg_replace('/((?<!:)(?:(?P<sheet>[A-Z]{1,})!|\'(?P<sheet2>[A-Z \(\)]{1,})\'!|)R\[?(?P<row>-?\d{0,})\]?C\[?(?P<cell>-?\d{0,})\]?(?!:))/six', '((\1))', $formula);
	preg_match_all('/(?<!:)(?:(?P<sheet>[A-Z]{1,})!|\'(?P<sheet2>[A-Z \(\)]{1,})\'!|)R\[?(?P<row>-?\d{0,})\]?C\[?(?P<cell>-?\d{0,})\]?(?!:)/six', $formula, $matches);

	//print_r( $matches );
	foreach( $matches[0] as $index => &$match ) {

		if( strlen( $matches['sheet'][$index] ) > 0 ) {
			$cur_sheet = $matches['sheet'][$index];
		}elseif( strlen( $matches['sheet2'][$index] ) > 0 ) {
			$cur_sheet = $matches['sheet2'][$index];
		}else{
			$cur_sheet = $sheet;	
		}

		$cur_row = (int)$row_index + (int)$matches['row'][$index];
		$cur_col = (int)$col_index + (int)$matches['cell'][$index];
		$cur_selected =& $spreadsheet_data[ $cur_sheet ][ $cur_row ][ $cur_col ];

		if( strlen($cur_selected['expanded']) ) {

		}elseif( strlen($cur_selected['formula']) ){
			$cur_selected['expanded'] = expand_eq( $cur_selected['formula'], $cur_row, $cur_col, $cur_sheet );
		}else{
			$cur_selected['expanded'] = " \$".sheet_clean($cur_sheet)."_{$cur_row}_{$cur_col} ";
			$GLOBALS[sheet_clean($cur_sheet)."_{$cur_row}_{$cur_col}"] = $cur_selected['value'];
		}

		$expanded_formula = str_replace( "(({$match}))", ' ( ' . $cur_selected[ 'expanded' ] . ' ) ', $expanded_formula );

	}

	//RANGE REPLACMENT
	$expanded_formula = preg_replace('/((?<!:)(?:(?P<sheet>[A-Z]{1,})!|\'(?P<sheet2>[A-Z \(\)]{1,})\'!|)R\[?(?P<row>-?\d{0,})\]?C\[?(?P<cell>-?\d{0,})\]?:R\[?(?P<row_to>-?\d{0,})\]?C\[?(?P<cell_to>-?\d{0,})\]?(?!:))/six', '///\1///', $expanded_formula);
	preg_match_all('/(?<!:)(?:(?P<sheet>[A-Z]{1,})!|\'(?P<sheet2>[A-Z \(\)]{1,})\'!|)R\[?(?P<row>-?\d{0,})\]?C\[?(?P<cell>-?\d{0,})\]?:R\[?(?P<row_to>-?\d{0,})\]?C\[?(?P<cell_to>-?\d{0,})\]?(?!:)/six', $expanded_formula, $matches);

	foreach( $matches[0] as $index => &$match ) {

		if( strlen( $matches['sheet'][$index] ) > 0 ) {
			$cur_sheet = $matches['sheet'][$index];
		}elseif( strlen( $matches['sheet2'][$index] ) > 0 ) {
			$cur_sheet = $matches['sheet2'][$index];
		}else{
			$cur_sheet = $sheet;	
		}

		$cur_row = (int)$row_index + (int)$matches['row'][$index];
		$cur_col = (int)$col_index + (int)$matches['cell'][$index];

		$match_expands = array();

		for( $i_row = $cur_row; $i_row <= $cur_row + ($matches['row_to'][$index] - $matches['row'][$index]); $i_row++ ) {
			for( $i_col = $cur_col; $i_col <= $cur_col + ($matches['cell_to'][$index] - $matches['cell'][$index]); $i_col++ ) {
				$cur_selected =& $spreadsheet_data[ $cur_sheet ][ $i_row ][ $i_col ];

				if( strlen($cur_selected['expanded']) ) {

				}elseif( strlen($cur_selected['formula']) ){
					$cur_selected['expanded'] = expand_eq( $cur_selected['formula'], $cur_row, $cur_col, $cur_sheet );
				}else{
					$cur_selected['expanded'] = " \$". sheet_clean($cur_sheet) ."_{$cur_row}_{$cur_col} ";
					$GLOBALS[sheet_clean($cur_sheet) . "_{$cur_row}_{$cur_col}"] = $cur_selected['value'];
				}

				$match_expands[] = ' ( ' . $cur_selected[ 'expanded' ] . ' ) ';

			}
		}

		$expanded_formula = str_replace( "///{$match}///", implode( '; ', $match_expands ) , $expanded_formula );

	}

	$expanded_formula = preg_replace('/([A-Z]{1,})\(/six', 'XML_XLS_\1 (', $expanded_formula);
	$expanded_formula = preg_replace('/(?<![=])=(?![=])/six', '==', $expanded_formula);

	return $expanded_formula;

}

//print_r( $spreadsheet_data );

function base_xls( $number ) {
	$str = base_convert($number - 1, 10, 26);
	$str = strtr( $str, '0123456789abcdefghijklmnopq', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
	for( $i = 0; $i <= strlen($str) - 2; $i++ ) {
		$str[ $i ] = chr(ord( $str[$i]) - 1 );
	}
	return $str;
}

function sheet_clean( $str ) {
	return preg_replace('/[^A-Z]/six', 'X', $str);	
}
