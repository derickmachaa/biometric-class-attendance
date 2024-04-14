#Create the db
#drop database online_attendance;
create database online_attendance;

use online_attendance;
#Create table student
create table tbl_Students(
AdmissionNo int(9) primary key,
FirstName varchar(50) not null,
LastName varchar(50) not null,
Email varchar(50) not null unique,
PhoneNo varchar(13) unique not null
);

#create table fingerprint
create table tbl_Fingerprints(
RightFinger int(3) unique,
LeftFinger int(3) unique,
AdmissionNo int(9) unique,
Foreign Key(AdmissionNo) references tbl_Students(AdmissionNo) ON DELETE cascade
);

#create table units
create table tbl_Units(
UnitCode varchar(10) primary key,
UnitName varchar(30) unique not null
);

#create table admin
create table tbl_Admin(
UserName varchar(20) primary key,
Email varchar(40) unique,
Password char(128)
);

#create table sessions
create table tbl_Sessions(
sessionID int(255) primary key auto_increment,
UnitCode varchar(10) not null,
Date date,
Venue varchar(20),
foreign key(UnitCode) references tbl_Units(UnitCode),
constraint unique index sessions ( UnitCode,Date)
);

#create table attendance
create table tbl_Attendance(
sessionID int(255),
AdmissionNo int(9) not null,
Attendend bool,
foreign key(AdmissionNo) references tbl_Students(AdmissionNo),
foreign key(sessionID) references tbl_Sessions(sessionID),
Constraint unique index attendance ( AdmissionNo,sessionID)
);

#Create table student units
create table tbl_StudentUnits(
AdmissionNo int(9) not null,
UnitCode varchar(10),
foreign key(AdmissionNo) references tbl_Students(AdmissionNo),
foreign key(UnitCode) references tbl_Units(UnitCode),
constraint unique index units ( AdmissionNo,UnitCode));

#do some inserts
insert into tbl_Units values
("CMT109","Database Systems"),
("CMT201","Logic Circuits"),
("CMT448", "Ethical Hacking");

#insert into students
insert into tbl_Students values
(1031890,"Derick","Kamoro","derickmachaa@gmail.com","0711218298"),
(1031891,"Ann","Wanjiru","ann@gmail.com","0701873605"),
(1031892,"Samuel","Waweru","saml@gmail.com","0780290290");

select * from tbl_StudentUnits;
select * from tbl_Units;
select * from tbl_Fingerprints;
select * from tbl_Attendance;
select * from tbl_Sessions;
#insert into tbl_units
insert into tbl_StudentUnits values
(1031891,"CMT201"),
(1031891,"CMT109");

insert into tbl_Sessions(UnitCode,Date,Venue) Values ("CMT448","1998-12-1","jubilee");
delete from tbl_Sessions where UnitCode = "CMT448";

-- cheat a bit for advanced stuff
DELIMITER $$
create trigger update_attendance after insert on tbl_Sessions 
for each row
begin
INSERT INTO tbl_Attendance (sessionID, AdmissionNo, Attendend) SELECT NEW.sessionID, AdmissionNo, FALSE FROM tbl_StudentUnits WHERE UnitCode = NEW.UnitCode;
end$$
DELIMITER ;
drop trigger update_attendance;

UPDATE tbl_Attendance join tbl_Fingerprints on tbl_Attendance.AdmissionNo = tbl_Fingerprints.AdmissionNo set tbl_Attendance.Attendend = FALSE where sessionID=10 and tbl_Fingerprints.RightFinger = 1 or tbl_Fingerprints.LeftFinger = 1;


SELECT tbl_Attendance.sessionID, tbl_Fingerprints.AdmissionNo FROM tbl_Attendance JOIN tbl_Fingerprints ON tbl_Attendance.AdmissionNo = tbl_Fingerprints.AdmissionNo WHERE tbl_Attendance.sessionID = 10  AND (tbl_Fingerprints.RightFinger = 3 OR tbl_Fingerprints.LeftFinger = 3);