<?php
echo p_chars("I(L)Verano")."\n";
echo dp_chars("I\(L\)Verano")."\n";

function p_chars( $text ) {
	$m_text = str_replace("(", "\(", $text);
	$m_text = str_replace(")", "\)", $m_text);
	return $m_text;
}

// Función para descodificar p_chars
function dp_chars( $text ) {
	$m_text = str_replace("\(", "(", $text);
	$m_text = str_replace("\)", ")", $m_text);
	return $m_text;
}
?>
