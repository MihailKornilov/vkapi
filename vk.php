<?php
/*
CREATE TABLE IF NOT EXISTS `vk_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `table_name` varchar(20) DEFAULT '',
  `table_id` int(10) unsigned DEFAULT '0',
  `parent_id` int(10) unsigned DEFAULT '0',
  `txt` text,
  `status` tinyint(3) unsigned DEFAULT '1',
  `viewer_id_add` int(10) unsigned DEFAULT '0',
  `dtime_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `viewer_id_del` int(10) unsigned DEFAULT '0',
  `dtime_del` datetime DEFAULT '0000-00-00 00:00:00',
  `child_del` text,
  KEY `i_table_id` (`table_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=cp1251;
*/

function _dbConnect() {
    global $mysql, $sqlQuery;
    $dbConnect = mysql_connect($mysql['host'], $mysql['user'], $mysql['pass'], 1) or die("Can't connect to database");
    mysql_select_db($mysql['database'], $dbConnect) or die("Can't select database");
    $sqlQuery = 0;
    query('SET NAMES `'.NAMES.'`', $dbConnect);
}//end of _dbConnect()
function query($sql) {
    global $sqlQuery, $sqlCount, $sqlTime;
    $t = microtime(true);
    $res = mysql_query($sql) or die($sql);
    $t = microtime(true) - $t;
    $sqlTime += $t;
    $t = round($t, 3);
    $sqlQuery .= $sql.' = <b style="color:#'.($t < 0.05 ? '999' : 'd22').'">'.$t.'</b><br /><br />';
    $sqlCount++;
    return $res;
}
function query_value($sql) {
    if(!$r = mysql_fetch_row(query($sql)))
        return false;
    return $r[0];
}
function query_selJson($sql) {
    $send = array();
    $q = query($sql);
    while($sp = mysql_fetch_row($q))
        $send[] = '{uid:'.$sp[0].',title:"'.$sp[1].'"}';
    return '['.implode(',',$send).']';
}
function query_ptpJson($sql) {//Ассоциативный массив
    $q = query($sql);
    $send = array();
    while($sp = mysql_fetch_row($q))
        $send[] = $sp[0].':'.(preg_match(REGEXP_NUMERIC, $sp[1]) ? $sp[1] : '"'.$sp[1].'"');
    return '{'.implode(',', $send).'}';
}

function _check($id, $txt='', $value=0) {
    return
        '<div class="check'.$value.'" id="'.$id.'_check">'.
        '<input type="hidden" id="'.$id.'" value="'.$value.'" />'.
        $txt.
        '</div>';
}//end of _check()
function _radio($id, $list, $value=0, $light=false) {
    $spisok = '';
    foreach($list as $uid => $title)
        $spisok .= '<div class="'.($uid == $value ? 'on' : 'off').($light ? ' l' : '').'" val="'.$uid.'">'.$title.'</div>';
    return
        '<div class="_radio" id="'.$id.'_radio">'.
        '<input type="hidden" id="'.$id.'" value="'.$value.'">'.
        $spisok.
        '</div>';
}//end of _radio()

function _end($count, $o1, $o2, $o5=false) {
    if($o5 === false) $o5 = $o2;
    if($count / 10 % 10 == 1)
        return $o5;
    else
        switch($count % 10) {
            case 1: return $o1;
            case 2: return $o2;
            case 3: return $o2;
            case 4: return $o2;
        }
    return $o5;
}//end of _end()

function win1251($txt) { return iconv('UTF-8','WINDOWS-1251',$txt); }
function utf8($txt) { return iconv('WINDOWS-1251','UTF-8',$txt); }
function curTime() { return strftime('%Y-%m-%d %H:%M:%S',time()); }

function _rightLink($id, $spisok, $val=0) {
    $a = '';
    foreach($spisok as $uid => $title)
        $a .= '<a'.($val == $uid ? ' class="sel"' : '').' val="'.$uid.'">'.$title.'</a>';
    return
        '<div class="rightLink" id="'.$id.'_rightLink">'.
        '<input type="hidden" id="'.$id.'" value="'.$val.'">'.
        $a.
        '</div>';
}//end of _rightLink()

function _vkComment($table, $id=0) {
    $sql = "SELECT *
            FROM `vk_comment`
            WHERE `status`=1
              AND `table_name`='".$table."'
              AND `table_id`=".intval($id)."
            ORDER BY `dtime_add` ASC";
    $count = 'Заметок нет';
    $units = '';
    $q = query($sql);
    if(mysql_num_rows($q)) {
        $comm = array();
        $v = array();
        while($r = mysql_fetch_assoc($q)) {
            if(!$r['parent_id'])
                $comm[$r['id']] = $r;
            elseif(isset($comm[$r['parent_id']]))
                $comm[$r['parent_id']]['childs'][] = $r;
            $v[$r['viewer_id_add']] = $r['viewer_id_add'];
        }
        $count = count($comm);
        $count = 'Всего '.$count.' замет'._end($count, 'ка', 'ки','ок');
        $v = _viewersInfo($v);
        $comm = array_reverse($comm);
        foreach($comm as $n => $r) {
            $childs = array();
            if(!empty($r['childs']))
                foreach($r['childs'] as $c)
                    $childs[] = _vkCommentChild($c['id'], $v[$c['viewer_id_add']], $c['txt'], $c['dtime_add']);
            $units .= _vkCommentUnit($r['id'], $v[$r['viewer_id_add']], $r['txt'], $r['dtime_add'], $childs, ($n+1));
        }
    }
    return '<div class="vkComment" val="'.$table.'_'.$id.'">'.
    '<div class=headBlue><div class="count">'.$count.'</div>Заметки</div>'.
    '<div class="add">'.
    '<textarea>Добавить заметку...</textarea>'.
    '<div class="vkButton"><button>Добавить</button></div>'.
    '</div>'.
    $units.
    '</div>';
}//end of _vkComment
function _vkCommentUnit($id, $viewer, $txt, $dtime, $childs=array(), $n=0) {
    return '<div class="cunit" val="'.$id.'">'.
    '<table class="t">'.
    '<tr><td class="ava">'.$viewer['photo'].
    '<td class="i">'.$viewer['link'].
    ($viewer['id'] == VIEWER_ID || VIEWER_ADMIN ? '<div class="img_del unit_del" title="Удалить заметку"></div>' : '').
    '<div class="ctxt">'.$txt.'</div>'.
    '<div class="cdat">'.FullDataTime($dtime, 1).
    '<SPAN'.($n == 1  && !empty($childs) ? ' class="hide"' : '').'> | '.
    '<a>'.(empty($childs) ? 'Комментировать' : 'Комментарии ('.count($childs).')').'</a>'.
    '</SPAN>'.
    '</div>'.
    '<div class="cdop'.(empty($childs) ? ' empty' : '').($n == 1 && !empty($childs) ? '' : ' hide').'">'.
    implode('', $childs).
    '<div class="cadd">'.
    '<textarea>Комментировать...</textarea>'.
    '<div class="vkButton"><button>Добавить</button></div>'.
    '</div>'.
    '</div>'.
    '</table></div>';
}//end of _vkCommentUnit()
function _vkCommentChild($id, $viewer, $txt, $dtime) {
    return '<div class="child" val="'.$id.'">'.
    '<table class="t">'.
    '<tr><td class="dava">'.$viewer['photo'].
    '<td class="di">'.$viewer['link'].
    ($viewer['id'] == VIEWER_ID || VIEWER_ADMIN ? '<div class="img_del child_del" title="Удалить комментарий"></div>' : '').
    '<div class="dtxt">'.$txt.'</div>'.
    '<div class="ddat">'.FullDataTime($dtime, 1).'</div>'.
    '</table></div>';
}//end of _vkCommentChild()

function _monthFull($n=0) {
    $mon = array(
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря'
    );
    return $n ? $mon[intval($n)] : $mon;
}//end of _monthFull
function _monthDef($n=0) {
    $mon = array(
        1 => 'январь',
        2 => 'февраль',
        3 => 'март',
        4 => 'апрель',
        5 => 'май',
        6 => 'июнь',
        7 => 'июль',
        8 => 'август',
        9 => 'сентябрь',
        10 => 'октябрь',
        11 => 'ноябрь',
        12 => 'декабрь'
    );
    return $n ? $mon[intval($n)] : $mon;
}//end of _monthFull
function _monthCut($n) {
    $mon = array(
        0 => '',
        1 => 'янв',
        2 => 'фев',
        3 => 'мар',
        4 => 'апр',
        5 => 'май',
        6 => 'июн',
        7 => 'июл',
        8 => 'авг',
        9 => 'сен',
        10 => 'окт',
        11 => 'ноя',
        12 => 'дек'
    );
    return $mon[intval($n)];
}//end of _monthCut
function FullData($value, $noyear=false) {//14 апреля 2010
    $d = explode('-', $value);
    return
        abs($d[2]).' '.
        _monthFull($d[1]).
        (!$noyear || date('Y') != $d[0] ? ' '.$d[0] : '');
}//end of FullData()
function FullDataTime($value, $cut=false) {//14 апреля 2010 в 12:45
    $arr = explode(' ',$value);
    $d = explode('-',$arr[0]);
    $t = explode(':',$arr[1]);
    return
        abs($d[2]).' '.
        ($cut ? _monthCut($d[1]) : _monthFull($d[1])).
        (date('Y') == $d[0] ? '' : ' '.$d[0]).
        ' в '.$t[0].':'.$t[1];
}//end of FullDataTime()
function _curMonday() { //Понедельник в текущей неделе
    // Номер текущего дня недели
    $time = time();
    $curDay = date("w", $time);
    if($curDay == 0) $curDay = 7;
    // Приведение дня к понедельнику
    $time -= 86400 * ($curDay - 1);
    return strftime('%Y-%m-%d', $time);
}//end of _curMonday()
function _curSunday() { //Воскресенье в текущей неделе
    $time = time();
    $curDay = date("w", $time);
    if($curDay == 0) $curDay = 7;
    $time += 86400 * (7 - $curDay);
    return strftime('%Y-%m-%d', $time);

}//end of _curSunday()

function _engRusChar($word) { //Перевод символов раскладки с английского на русский
    $char = array(
        'q' => 'й',
        'w' => 'ц',
        'e' => 'у',
        'r' => 'к',
        't' => 'е',
        'y' => 'н',
        'u' => 'г',
        'i' => 'ш',
        'o' => 'щ',
        'p' => 'з',
        '[' => 'х',
        ']' => 'ъ',
        'a' => 'ф',
        's' => 'ы',
        'd' => 'в',
        'f' => 'а',
        'g' => 'п',
        'h' => 'р',
        'j' => 'о',
        'k' => 'л',
        'l' => 'д',
        ';' => 'ж',
        "'" => 'э',
        'z' => 'я',
        'x' => 'ч',
        'c' => 'с',
        'v' => 'м',
        'b' => 'и',
        'n' => 'т',
        'm' => 'ь',
        ',' => 'б',
        '.' => 'ю',
        '0' => '0',
        '1' => '1',
        '2' => '2',
        '3' => '3',
        '4' => '4',
        '5' => '5',
        '6' => '6',
        '7' => '7',
        '8' => '8',
        '9' => '9'
    );
    $send = '';
    for($n = 0; $n < strlen($word); $n++)
        if(isset($char[$word[$n]]))
            $send .= $char[$word[$n]];
    return $send;
}
function unescape($str){
    $escape_chars = '0410 0430 0411 0431 0412 0432 0413 0433 0490 0491 0414 0434 0415 0435 0401 0451 0404 0454 '.
        '0416 0436 0417 0437 0418 0438 0406 0456 0419 0439 041A 043A 041B 043B 041C 043C 041D 043D '.
        '041E 043E 041F 043F 0420 0440 0421 0441 0422 0442 0423 0443 0424 0444 0425 0445 0426 0446 '.
        '0427 0447 0428 0448 0429 0449 042A 044A 042B 044B 042C 044C 042D 044D 042E 044E 042F 044F';
    $russian_chars = 'А а Б б В в Г г Ґ ґ Д д Е е Ё ё Є є Ж ж З з И и І і Й й К к Л л М м Н н О о П п Р р С с Т т У у Ф ф Х х Ц ц Ч ч Ш ш Щ щ Ъ ъ Ы ы Ь ь Э э Ю ю Я я';
    $e = explode(' ', $escape_chars);
    $r = explode(' ', $russian_chars);
    $rus_array = explode('%u', $str);
    $new_word = str_replace($e, $r, $rus_array);
    $new_word = str_replace('%20', ' ', $new_word);
    return implode($new_word);
}