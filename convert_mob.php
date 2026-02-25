<?php
// 1. ตั้งค่าโฟลเดอร์ที่เก็บไฟล์ .txt (เพิ่มโฟลเดอร์ fields เข้ามาแล้ว)
// ** อย่าลืมเอาโฟลเดอร์ dungeons และ fields มาวางไว้ในโฟลเดอร์ mobs ระดับเดียวกับไฟล์นี้ด้วยนะครับ
$folders = ['./mobs/dungeons', './mobs/fields']; 
$sql_output_file = 'mob_spawn.sql';

$spawn_data = [];
$files = [];

// วนลูปดึงรายชื่อไฟล์ .txt จากทั้ง 2 โฟลเดอร์
foreach ($folders as $folder) {
    $found_files = glob($folder . '/*.txt');
    if ($found_files) {
        $files = array_merge($files, $found_files);
    }
}

if (empty($files)) {
    die("ไม่พบไฟล์ .txt ในโฟลเดอร์ที่ระบุเลยครับ ลองเช็คชื่อโฟลเดอร์อีกทีนะ");
}

foreach ($files as $file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);
        // ข้ามบรรทัดที่เป็น comment (//) หรือบรรทัดว่าง
        if (empty($line) || strpos($line, '//') === 0) continue;

        // โครงสร้าง rAthena จะใช้ Tab (\t) ในการเว้นวรรคคำสั่ง
        $parts = explode("\t", $line);
        
        // เช็คว่ามีข้อมูลครบ และเป็นประเภท monster หรือ boss_monster
        if (count($parts) >= 4 && ($parts[1] === 'monster' || $parts[1] === 'boss_monster')) {
            
            // ส่วนที่ 1: ชื่อ Map (เช่น prt_fild01,0,0) ตัดเอาแค่ชื่อแมพ
            $map_info = explode(',', $parts[0]);
            $map_name = trim($map_info[0]);

            // ส่วนที่ 4: ID และจำนวน (เช่น 1002,50 หรือ 1289,1,7200000)
            $mob_info = explode(',', $parts[3]);
            $mob_id = (int)trim($mob_info[0]);
            $amount = isset($mob_info[1]) ? (int)trim($mob_info[1]) : 1; 

            // สร้าง Key เพื่อรวมจำนวนมอนสเตอร์ชนิดเดียวกันที่อยู่ในแมพเดียวกันให้เป็นก้อนเดียว
            $key = $map_name . '_' . $mob_id;
            
            if (!isset($spawn_data[$key])) {
                $spawn_data[$key] = [
                    'map' => $map_name,
                    'mob_id' => $mob_id,
                    'amount' => 0
                ];
            }
            $spawn_data[$key]['amount'] += $amount;
        }
    }
}

// 2. เริ่มสร้างโครงสร้างไฟล์ SQL
$sql_content = "-- สร้างตาราง mob_spawn (ถ้ายังไม่มี)\n";
$sql_content .= "CREATE TABLE IF NOT EXISTS `mob_spawn` (\n";
$sql_content .= "  `map` varchar(24) NOT NULL,\n";
$sql_content .= "  `mob_id` smallint(6) unsigned NOT NULL,\n";
$sql_content .= "  `amount` smallint(6) unsigned NOT NULL\n";
$sql_content .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8;\n\n";

$sql_content .= "-- ล้างข้อมูลเก่าก่อนอัปเดตใหม่\n";
$sql_content .= "TRUNCATE TABLE `mob_spawn`;\n\n";

$sql_content .= "-- ข้อมูลจุดเกิด\n";

// แบ่ง Insert ทีละ 500 บรรทัดป้องกัน Query ยาวเกินไป
$chunks = array_chunk($spawn_data, 500);
foreach ($chunks as $chunk) {
    $sql_content .= "INSERT INTO `mob_spawn` (`map`, `mob_id`, `amount`) VALUES\n";
    $values = [];
    foreach ($chunk as $row) {
        $values[] = "('{$row['map']}', {$row['mob_id']}, {$row['amount']})";
    }
    $sql_content .= implode(",\n", $values) . ";\n\n";
}

// 3. บันทึกเป็นไฟล์ SQL
file_put_contents($sql_output_file, $sql_content);
echo "<h3>✅ สำเร็จ!</h3>";
echo "สร้างไฟล์ <b>" . $sql_output_file . "</b> เรียบร้อยแล้ว<br>";
echo "อ่านไฟล์จากโฟลเดอร์ dungeons และ fields รวมทั้งหมด <b>" . count($files) . "</b> ไฟล์<br>";
echo "พบข้อมูลจุดเกิดทั้งหมด <b>" . count($spawn_data) . "</b> รายการ";
?>