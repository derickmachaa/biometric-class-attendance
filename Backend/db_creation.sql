#Create the db
create database online_attendance;

use online_attendance;
#Create table student
create table tbl_student(
AdmissionNo int(9) primary key,
FirstName varchar(50) not null,
LastName varchar(50) not null,
Email varchar(50) not null unique,
PhoneNo varchar(13) unique not null
);

#create table fingerprint
create table tbl_fingerprint(
RightFinger int(3) unique,
LeftFinger int(3) unique,
AdmissionNo int(9),
Foreign Key(AdmissionNo) references tbl_student(AdmissionNo) ON DELETE cascade
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

#create table attendance
create table tbl_Attendance(
UnitCode varchar(10),
AdmissionNo