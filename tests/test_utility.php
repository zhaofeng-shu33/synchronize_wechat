<?php
//! \brief  fetch the html web page from $url and saves it to $html_file_name
//! \return $html content of the file
function fetch_html($html_file_name, $url)
{
    if(file_exists($html_file_name)){
        $html = file_get_contents($html_file_name);
        if(strlen($html)>0)
            return $html;
    }
        //check for right output of function `get_html`
    $html = get_html($url);
    file_put_contents($html_file_name, $html);
    return $html;
}
?>