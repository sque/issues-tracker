<?php
Stupid::add_rule('view_file',
    array('type' => 'url_path', 'chunk[2]' => '/^(\d+)$/', 'chunk[3]' => '/^(.+)$/'));
Stupid::set_default_action('not_found');
Stupid::chain_reaction();

function view_file($id, $filename)
{
    if (!($a = Attachment::open($id)))
        not_found();
        
    if ($a->filename != $filename)
        not_found();
        
    $a->dump_file();
}
?>
