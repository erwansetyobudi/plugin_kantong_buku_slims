<?php
/*
 * File: index.php
 * Created on Wed Oct 07 2026
 * Last Updated: Wed Oct 07 2026 6:12:19 PM
 * Author: Erwan Setyo Budi
 * Email: erwans818@gmail.com
 * License: The GNU General Public License, Version 3 (GPL-3.0) - Copyright (C) 2026 Erwan Setyo Budi. This program is free software.
 */

defined('INDEX_AUTH') OR die('Direct access not allowed!');
require_once SB.'admin/default/session.inc.php';
require_once SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require_once SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require_once SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require_once SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';


// Otorisasi mengikuti autentikasi admin SLiMS/plugin_container.
// Tidak memakai utility::havePrivilege('bibliography','r') karena pada sebagian
// instalasi SLiMS 9 hak menu plugin tidak dipetakan ke privilege bibliography.
if (!isset($_SESSION['kantong_buku']) || !is_array($_SESSION['kantong_buku'])) { $_SESSION['kantong_buku'] = []; }

function kb_h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function kb_route($extra=[]){
    $q = $_GET;
    foreach ($extra as $k=>$v) {
        if ($v === null) unset($q[$k]); else $q[$k]=$v;
    }
    return $_SERVER['PHP_SELF'].'?'.http_build_query($q);
}
function kb_settings($dbs){
    $d=['items_per_row'=>2,'libraryname'=>'PERPUSTAKAAN','schoolname'=>'','max_print'=>8];
    $q=$dbs->query("SELECT setting_value FROM setting WHERE setting_name='kantong_buku_plugin_settings' LIMIT 1");
    if($q && ($r=$q->fetch_assoc()) && !empty($r['setting_value'])){
        $x=@unserialize($r['setting_value']);
        if(is_array($x)) $d=array_merge($d,$x);
    }
    return $d;
}
$settings=kb_settings($dbs);
$action=isset($_GET['action'])?trim($_GET['action']):'';

if ($action==='clear') {
    $_SESSION['kantong_buku']=[];
    utility::jsToastr('Kantong Buku', 'Antrian cetak telah dikosongkan.', 'success');
    echo '<script>top.$(\'#queueCount\').html(\'0\');</script>'; exit;
}

if ($action==='save_settings' && $_SERVER['REQUEST_METHOD']==='POST') {
    $new=[
      'items_per_row'=>max(1,min(3,(int)($_POST['items_per_row']??2))),
      'libraryname'=>trim((string)($_POST['libraryname']??'')),
      'schoolname'=>trim((string)($_POST['schoolname']??'')),
      'max_print'=>max(1,min(100,(int)($_POST['max_print']??8)))
    ];
    $name='kantong_buku_plugin_settings'; $val=$dbs->escape_string(serialize($new));
    $dbs->query("REPLACE INTO setting (setting_name,setting_value) VALUES ('$name','$val')");
    utility::jsAlert('Pengaturan Kantong Buku berhasil disimpan.');
    echo '<script>location.href='.json_encode(kb_route(['action'=>null])).';</script>'; exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['itemAction'])) {
    $ids=$_POST['itemID']??[];
    if(!is_array($ids)) $ids=[$ids];
    $max=(int)$settings['max_print'];
    foreach($ids as $id){
        $id=trim((string)$id);
        if($id==='' || isset($_SESSION['kantong_buku'][$id])) continue;
        if(count($_SESSION['kantong_buku']) >= $max) break;
        $_SESSION['kantong_buku'][$id]=$id;
    }
    echo '<script>top.$(\'#queueCount\').html(\''.count($_SESSION['kantong_buku']).'\');</script>';
    utility::jsToastr('Kantong Buku', 'Data terpilih ditambahkan ke antrian cetak.', 'success'); exit;
}

if ($action==='print') {
    if (empty($_SESSION['kantong_buku'])) {
        utility::jsToastr('Kantong Buku', 'Belum ada data dalam antrian cetak.', 'error');
        exit;
    }

    $escaped=[];
    foreach(array_keys($_SESSION['kantong_buku']) as $id) $escaped[]="'".$dbs->escape_string($id)."'";
    $sql="SELECT i.item_code,i.biblio_id,i.call_number,b.title
          FROM item i
          LEFT JOIN biblio b ON b.biblio_id=i.biblio_id
          WHERE i.item_code IN(".implode(',',$escaped).")
          ORDER BY b.title";
    $q=$dbs->query($sql);
    $rows=[];
    if($q) while($r=$q->fetch_assoc()) $rows[]=$r;

    $img = SWB.'plugins/kantong_buku/assets/kantong.png';
    $per=max(1,(int)$settings['items_per_row']);

    ob_start();
    ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Cetak Kantong Buku</title>
<style>
@page{margin:8mm}
*{box-sizing:border-box}
body{font-family:Arial,sans-serif;margin:0;color:#111;background:#fff}
.toolbar{padding:10px;text-align:left}
.toolbar a{font-size:14px}
.sheet{display:grid;grid-template-columns:repeat(<?= $per ?>,11cm);gap:8mm;justify-content:center;padding:0 8mm 8mm}
.pocket{position:relative;width:11cm;height:12cm;background:url('<?=kb_h($img)?>') center/11cm 12cm no-repeat;break-inside:avoid}
.content{position:absolute;left:2.65cm;right:.65cm;top:8.45cm;font-size:11.5pt;line-height:1.42}
.library{font-weight:700}.school{margin-bottom:4px}
.row{display:grid;grid-template-columns:3.1cm .25cm 1fr;gap:0}
@media print{.toolbar{display:none}.sheet{gap:5mm;padding:0}}
</style>
</head>
<body>
<div class="toolbar"><a href="#" onclick="window.print();return false;">Print</a></div>
<div class="sheet">
<?php foreach($rows as $r): ?>
<div class="pocket"><div class="content">
<div class="library"><?=kb_h($settings['libraryname'])?></div>
<div class="school"><?=kb_h($settings['schoolname'])?></div>
<div class="row"><span>Nomor Inven</span><span>:</span><span><?=kb_h($r['item_code'])?></span></div>
<div class="row"><span>Nomor Panggil</span><span>:</span><span><?=kb_h($r['call_number'])?></span></div>
</div></div>
<?php endforeach; ?>
</div>
</body>
</html>
<?php
    $content=ob_get_clean();

    $uname=isset($_SESSION['uname']) ? $_SESSION['uname'] : 'admin';
    $print_file_name='kantong_buku_print_result_'.preg_replace('/[^a-z0-9_-]+/i','_',strtolower($uname)).'.html';
    $file_write=@file_put_contents(UPLOAD.$print_file_name,$content);

    if($file_write){
        // Pertahankan antrian agar dapat dicetak ulang; hanya buka hasil dalam Colorbox iframe.
        echo '<script type="text/javascript">
        top.$.colorbox({
            href: "'.SWB.FLS.'/'.$print_file_name.'?v='.date('YmdHis').'",
            iframe: true,
            width: "90%",
            height: "90%",
            title: "Cetak Kantong Buku"
        });
        </script>';
    } else {
        utility::jsToastr('Kantong Buku','Gagal membuat file cetak. Pastikan folder files/ dapat ditulis.','error');
    }
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    exit;
}

$keyword=trim((string)($_GET['keywords']??''));
?>
<div class="menuBox"><div class="menuBoxInner printIcon"><div class="per_title"><h2>Kantong Buku</h2></div><div class="sub_section">
<div class="btn-group"><a target="blindSubmit" href="<?=kb_h(kb_route(['action'=>'clear']))?>" class="btn btn-default notAJAX">Kosongkan Antrian</a><a target="blindSubmit" href="<?=kb_h(kb_route(['action'=>'print']))?>" class="btn btn-default notAJAX">Cetak Kantong Buku</a></div>
<form name="search" action="<?=kb_h(kb_route(['keywords'=>null,'action'=>null]))?>" id="search" method="get" class="form-inline">
<?php foreach($_GET as $k=>$v): if(!in_array($k,['keywords','action'],true)&&!is_array($v)): ?><input type="hidden" name="<?=kb_h($k)?>" value="<?=kb_h($v)?>"><?php endif; endforeach; ?>
<?=__('Search')?> <input type="text" name="keywords" class="form-control col-md-3" value="<?=kb_h($keyword)?>"> <input type="submit" id="doSearch" value="<?=__('Search')?>" class="s-btn btn btn-default"></form></div>
<div class="infoBox">Maksimum <strong class="text-danger"><?=(int)$settings['max_print']?></strong> data dapat dicetak sekali proses. Saat ini ada <strong id="queueCount" class="text-danger"><?=count($_SESSION['kantong_buku']??[])?></strong> data dalam antrian.</div></div></div>
<?php
$datagrid=new simbio_datagrid();
$table_spec='item AS i INNER JOIN biblio AS b ON b.biblio_id=i.biblio_id';
$datagrid->setSQLColumn("i.item_code", "i.item_code AS '".__('Item Code')."'", "i.call_number AS '".__('Call Number')."'", "b.title AS '".__('Title')."'");
$datagrid->setSQLorder('i.last_update DESC');
if($keyword!==''){$keywords=utility::filterData('keywords','get',true,true,true);$datagrid->setSQLcriteria("(i.item_code LIKE '%".$keywords."%' OR i.call_number LIKE '%".$keywords."%' OR b.title LIKE '%".$keywords."%')");}
$datagrid->table_attr='id="dataList" class="s-table table"';
$datagrid->table_header_attr='class="dataListHeader" style="font-weight:bold;"';
$datagrid->edit_property=false;
$datagrid->chbox_property=['itemID',__('Add')];
$datagrid->chbox_action_button='Tambahkan ke Antrian Cetak';
$datagrid->chbox_confirm_msg='Tambahkan data terpilih ke antrian cetak?';
$datagrid->chbox_form_URL=$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
$datagrid_result=$datagrid->createDataGrid($dbs,$table_spec,20,true);
if($keyword!=='') echo '<div class="infoBox">Ditemukan <strong>'.$datagrid->num_rows.'</strong> data untuk pencarian: &quot;'.kb_h($keyword).'&quot;.</div>';
echo $datagrid_result;
?>
<div class="menuBox" style="margin-top:15px"><div class="menuBoxInner"><div class="per_title"><h2>Pengaturan Cetak Kantong Buku</h2></div><div class="sub_section">
<form method="post" action="<?=kb_h(kb_route(['action'=>'save_settings']))?>" class="form-horizontal">
<div class="form-group"><label class="col-sm-2 control-label">Nama Perpustakaan</label><div class="col-sm-4"><input class="form-control" name="libraryname" value="<?=kb_h($settings['libraryname'])?>"></div><label class="col-sm-2 control-label">Nama Institusi</label><div class="col-sm-4"><input class="form-control" name="schoolname" value="<?=kb_h($settings['schoolname'])?>"></div></div>
<div class="form-group"><label class="col-sm-2 control-label">Kantong per Baris</label><div class="col-sm-4"><input class="form-control" type="number" min="1" max="3" name="items_per_row" value="<?=(int)$settings['items_per_row']?>"></div><label class="col-sm-2 control-label">Maksimum Sekali Cetak</label><div class="col-sm-4"><input class="form-control" type="number" min="1" max="100" name="max_print" value="<?=(int)$settings['max_print']?>"></div></div>
<div class="form-group"><div class="col-sm-offset-2 col-sm-10"><button class="btn btn-primary" type="submit">Simpan Pengaturan</button></div></div></form></div></div></div>
