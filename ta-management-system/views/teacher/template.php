<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แบบฟอร์มแจ้งความประสงค์ในการจ้างนักศึกษาช่วยสอน</title>
    <style>
        body {
            font-family: 'Sarabun', 'Cordia New', Tahoma, sans-serif;
            font-size: 14px;
            line-height: 1.3;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .page-container {
            width: 210mm; /* A4 Width */
            max-width: 100%;
            background: white;
            box-sizing: border-box;
        }
        
        .header-warning {
            text-align: center;
            margin-bottom: 5px;
        }
        .header-warning span {
            background-color: #ffffcc;
            color: #ff0000;
            padding: 2px 10px;
        }

        .date-section {
            text-align: right;
            margin-bottom: 15px;
        }

        .titles {
            text-align: center;
            margin-bottom: 15px;
        }
        .titles h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .titles h3 {
            margin: 0;
            font-size: 16px;
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        table.bordered th, table.bordered td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: middle;
        }

        .red-note {
            color: red;
            font-size: 12px;
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 5px;
        }

        input[type="text"].line-input {
            border: none;
            border-bottom: 1px dashed #000;
            background: transparent;
            outline: none;
            font-family: inherit;
            font-size: 14px;
            padding: 0 2px;
        }
        
        input[type="text"].table-input {
            width: 100%;
            border: none;
            box-sizing: border-box;
            background: transparent;
            outline: none;
            font-family: inherit;
        }

        input[type="text"].box-input {
            border: 1px solid #000;
            padding: 2px 5px;
            font-family: inherit;
        }

        input[type="checkbox"] {
            vertical-align: middle;
            margin-top: -2px;
        }

        .work-details {
            margin-top: 10px;
        }
        .work-details-title {
            text-align: center;
            text-decoration: underline;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .work-row {
            margin-bottom: 5px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .warning-box {
            border: 2px dashed #a0c4ff;
            background-color: #ffffe6;
            color: red;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
        }

    </style>
</head>
<body>

<div class="page-container">
    
    <div class="header-warning">
        <span>กรอกข้อมูลในแบบฟอร์มนี้ด้วยการพิมพ์เท่านั้นและให้นำส่งในรูปแบบของไฟล์</span>
    </div>

    <div class="date-section">
        วันที่ส่งฟอร์ม <input type="text" class="box-input" style="width: 150px;">
    </div>

    <div class="titles">
        <h2>แบบฟอร์มแจ้งความประสงค์ในการจ้างนักศึกษาช่วยสอนและช่วยคุมปฏิบัติการ</h2>
        <h3>ภาควิชาคอมพิวเตอร์ คณะวิทยาศาสตร์ มหาวิทยาลัยศิลปากร</h3>
    </div>

    <table class="bordered">
        <tr>
            <td style="width: 15%;">รหัสวิชา</td>
            <td style="width: 25%;"><input type="text" class="table-input"></td>
            <td style="width: 35%;">ภาคการศึกษา &nbsp;
                <input type="checkbox"> ต้น &nbsp;
                <input type="checkbox"> ปลาย &nbsp;
                <input type="checkbox"> ฤดูร้อน
            </td>
            <td style="width: 25%;">ปีการศึกษา <input type="text" class="line-input" style="width: 80px;"></td>
        </tr>
        <tr>
            <td>ชื่อรายวิชา</td>
            <td colspan="3"><input type="text" class="table-input"></td>
        </tr>
        <tr>
            <td>อาจารย์ผู้สอน</td>
            <td colspan="3"><input type="text" class="table-input"></td>
        </tr>
        <tr>
            <td colspan="3">
                <input type="checkbox"> นักศึกษาช่วยสอน (TA) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="checkbox"> นักศึกษาช่วยคุม (Lab Boy)
            </td>
            <td>จำนวน <input type="text" class="line-input" style="width: 80px;"> คน</td>
        </tr>
    </table>

    <div class="red-note"><u>หมายเหตุ</u> ให้กรอกข้อมูลสำหรับ TA กับ Lab Boy แยกคนละฟอร์ม กรณีที่ช่วงเวลาของผู้ปฏิบัติงานแตกต่างกันให้กรอกแยกคนละแบบฟอร์ม</div>

    <table style="border: none; width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 75%; vertical-align: top; padding: 0;">
                <table class="bordered" style="margin: 0; width: 100%;">
                    <tr>
                        <th style="width: 8%; text-align: center; font-weight: normal;"></th>
                        <th style="width: 32%; text-align: center; font-weight: normal;">รหัสนักศึกษา</th>
                        <th style="width: 60%; text-align: center; font-weight: normal;">คำนำหน้าชื่อ-ชื่อ-นามสกุล</th>
                    </tr>
                    <tr><td style="text-align: center;">1.</td><td><input type="text" class="table-input"></td><td><input type="text" class="table-input"></td></tr>
                    <tr><td style="text-align: center;">2.</td><td><input type="text" class="table-input"></td><td><input type="text" class="table-input"></td></tr>
                    <tr><td style="text-align: center;">3.</td><td><input type="text" class="table-input"></td><td><input type="text" class="table-input"></td></tr>
                    <tr><td style="text-align: center;">4.</td><td><input type="text" class="table-input"></td><td><input type="text" class="table-input"></td></tr>
                    <tr><td style="text-align: center;">5.</td><td><input type="text" class="table-input"></td><td><input type="text" class="table-input"></td></tr>
                    <tr><td style="text-align: center;">6.</td><td><input type="text" class="table-input"></td><td><input type="text" class="table-input"></td></tr>
                    <tr><td style="text-align: center;">7.</td><td><input type="text" class="table-input"></td><td><input type="text" class="table-input"></td></tr>
                    <tr><td style="text-align: center;">8.</td><td><input type="text" class="table-input"></td><td><input type="text" class="table-input"></td></tr>
                    <tr><td style="text-align: center;">9.</td><td><input type="text" class="table-input"></td><td><input type="text" class="table-input"></td></tr>
                    <tr><td style="text-align: center;">10.</td><td><input type="text" class="table-input"></td><td><input type="text" class="table-input"></td></tr>
                </table>
            </td>
            <td style="width: 25%; vertical-align: stretch; padding-left: 10px;">
                <div class="warning-box" style="height: 100%; display: flex; align-items: center; justify-content: center; box-sizing: border-box;">
                    <span>
                        ขอให้แนบเอกสารจาก<br>เว็บ REG ที่ระบุข้อมูล<br>รหัส ชื่อ-นามสกุล ของ<br>นักศึกษาแต่ละราย<br>
                        <span style="text-decoration: underline;">พร้อมทั้งตรวจสอบข้อมูล<br>ดังกล่าวที่กรอกใน<br>แบบฟอร์มนี้ให้ถูกต้องด้วย</span><br>
                        หากสะกดผิดพลาดจะ<br>ไม่สามารถเบิกเงินได้
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <div class="work-details">
        <div class="work-details-title">รายละเอียดการปฏิบัติงาน</div>

        <div class="section-title">วันปฏิบัติงานในแต่ละสัปดาห์</div>
        <div style="margin-left: 15px;">
            <div class="work-row">
                วัน <input type="text" class="line-input" style="width: 100px; margin: 0 10px;">
                เวลา <input type="text" class="line-input" style="width: 70px; margin: 0 10px;">
                ถึง <input type="text" class="line-input" style="width: 70px; margin: 0 10px;"> น.
                <span style="margin-left: 20px;">รวมวันละ <input type="text" class="line-input" style="width: 50px; margin: 0 10px;"> ชั่วโมง</span>
            </div>
            <div class="work-row">
                วัน <input type="text" class="line-input" style="width: 100px; margin: 0 10px;">
                เวลา <input type="text" class="line-input" style="width: 70px; margin: 0 10px;">
                ถึง <input type="text" class="line-input" style="width: 70px; margin: 0 10px;"> น.
                <span style="margin-left: 20px;">รวมวันละ <input type="text" class="line-input" style="width: 50px; margin: 0 10px;"> ชั่วโมง</span>
            </div>
            <div class="work-row">
                วัน <input type="text" class="line-input" style="width: 100px; margin: 0 10px;">
                เวลา <input type="text" class="line-input" style="width: 70px; margin: 0 10px;">
                ถึง <input type="text" class="line-input" style="width: 70px; margin: 0 10px;"> น.
                <span style="margin-left: 20px;">รวมวันละ <input type="text" class="line-input" style="width: 50px; margin: 0 10px;"> ชั่วโมง</span>
            </div>
        </div>

        <div class="section-title">จำนวนครั้งปฏิบัติงานในแต่ละเดือน</div>
        <div style="margin-left: 15px;">
            <div class="work-row">
                เดือน <input type="text" class="line-input" style="width: 90px; margin: 0 10px;">
                พ.ศ. <input type="text" class="line-input" style="width: 70px; margin: 0 10px;">
                จำนวน <input type="text" class="line-input" style="width: 40px; margin: 0 5px;"> ครั้ง 
                คือ วันที่ของเดือน <input type="text" class="line-input" style="width: 250px; margin-left: 10px;">
            </div>
            <div class="work-row">
                เดือน <input type="text" class="line-input" style="width: 90px; margin: 0 10px;">
                พ.ศ. <input type="text" class="line-input" style="width: 70px; margin: 0 10px;">
                จำนวน <input type="text" class="line-input" style="width: 40px; margin: 0 5px;"> ครั้ง 
                คือ วันที่ของเดือน <input type="text" class="line-input" style="width: 250px; margin-left: 10px;">
            </div>
            <div class="work-row">
                เดือน <input type="text" class="line-input" style="width: 90px; margin: 0 10px;">
                พ.ศ. <input type="text" class="line-input" style="width: 70px; margin: 0 10px;">
                จำนวน <input type="text" class="line-input" style="width: 40px; margin: 0 5px;"> ครั้ง 
                คือ วันที่ของเดือน <input type="text" class="line-input" style="width: 250px; margin-left: 10px;">
            </div>
            <div class="work-row">
                เดือน <input type="text" class="line-input" style="width: 90px; margin: 0 10px;">
                พ.ศ. <input type="text" class="line-input" style="width: 70px; margin: 0 10px;">
                จำนวน <input type="text" class="line-input" style="width: 40px; margin: 0 5px;"> ครั้ง 
                คือ วันที่ของเดือน <input type="text" class="line-input" style="width: 250px; margin-left: 10px;">
            </div>
            <div class="work-row">
                เดือน <input type="text" class="line-input" style="width: 90px; margin: 0 10px;">
                พ.ศ. <input type="text" class="line-input" style="width: 70px; margin: 0 10px;">
                จำนวน <input type="text" class="line-input" style="width: 40px; margin: 0 5px;"> ครั้ง 
                คือ วันที่ของเดือน <input type="text" class="line-input" style="width: 250px; margin-left: 10px;">
            </div>
        </div>

        <div class="section-title">สรุปจำนวนปฏิบัติงานรวมทั้งหมด</div>
        <div class="work-row" style="margin-left: 15px;">
            ทั้งหมด <input type="text" class="line-input" style="width: 40px; margin: 0 5px;"> ครั้ง x 
            <input type="text" class="line-input" style="width: 40px; margin: 0 5px;"> คน x 
            <input type="text" class="line-input" style="width: 40px; margin: 0 5px;"> ชั่วโมง x 
            <input type="text" class="line-input" style="width: 40px; margin: 0 5px;"> บาท 
            <span style="margin-left: 20px;">รวมเป็นเงิน <input type="text" class="line-input" style="width: 100px; margin: 0 10px;"> บาท</span>
        </div>
    </div>

</div>

</body>
</html>