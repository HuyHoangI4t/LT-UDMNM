@echo off
title Chạy Dự Án Fullstack

:: Chạy Frontend trong cửa sổ mới
start "Frontend" cmd /k "cd /d frontend && npm start"

:: Chạy Backend trong cửa sổ mới
start "Backend" cmd /k "cd /d backend && composer dev"

echo Đang khởi động Frontend và Backend...
pause