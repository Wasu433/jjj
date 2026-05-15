<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>

body{
font-family:sarabun;
}

table{
width:100%;
border-collapse:collapse;
margin-bottom:20px;
}

th,td{
border:1px solid #000;
padding:6px;
font-size:12px;
}

h2{
margin-top:30px;
}

</style>
</head>
<body>

<h1>รายงานผู้ช่วยสอน (TA)</h1>

<?php foreach($grouped_subjects as $subject): ?>

<h2>
<?= $subject['subject_code'] ?> - <?= $subject['subject_name'] ?>
</h2>

<table>
<tr>
<th width="10%">ลำดับ</th>
<th width="30%">รหัสนักศึกษา</th>
<th width="40%">ชื่อ</th>
<th width="20%">เกรด</th>
</tr>

<?php foreach($subject['students'] as $i=>$student): ?>

<tr>
<td><?= $i+1 ?></td>
<td><?= $student['student_id'] ?></td>
<td><?= $student['student_name'] ?></td>
<td><?= $student['student_grade'] ?></td>
</tr>

<?php endforeach; ?>

</table>

<?php endforeach; ?>

</body>
</html>