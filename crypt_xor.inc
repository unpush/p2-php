<?php
/* Thanx.
XOR encryption (Author: TheWomble)
http://www.free2code.net/code/php/1/code_9.php
*/

function encrypt_xor($input, $key){ 
	$key_len = strlen($key) - 1; 
	$input_len = strlen($input) - 1; 

	while($input_cnt <= $input_len){ 
		if($key_cnt >= $key_len){ 
			$cur_key = $key[$key_cnt]; 
			$key_cnt = 0; 
		}else{ 
			$cur_key = $key[$key_cnt]; 
			$key_cnt++; 
		} 
	
		$output .= chr(ord($input[$input_cnt])^ord($cur_key)); 
		$input_cnt++; 
	} 
	return $output; 
} 

function decrypt_xor($input, $key){ 
	$key_len = strlen($key) - 1; 
	$input_len = strlen($input) - 1; 
	
	while($input_cnt <= $input_len){ 
		if($key_cnt >= $key_len){ 
			$cur_key = $key[$key_cnt]; 
			$key_cnt = 0; 
		}else{ 
			$cur_key = $key[$key_cnt]; 
			$key_cnt++; 
		} 
		
		$output .= chr(ord($input[$input_cnt])^ord($cur_key)); 
		$input_cnt++; 
	} 
	return $output; 
}
?>