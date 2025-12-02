// Prevents additional console window on Windows in release
#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use serde::{Deserialize, Serialize};

#[derive(Debug, Serialize, Deserialize)]
struct SystemInfo {
    os: String,
    arch: String,
    version: String,
}

// Simple greeting command
#[tauri::command]
fn greet(name: &str) -> String {
    format!("Hello, {}! Welcome to Tauri 2.2!", name)
}

// Command with multiple parameters
#[tauri::command]
fn calculate(a: i32, b: i32, operation: &str) -> Result<i32, String> {
    match operation {
        "add" => Ok(a + b),
        "subtract" => Ok(a - b),
        "multiply" => Ok(a * b),
        "divide" => {
            if b == 0 {
                Err("Division by zero".to_string())
            } else {
                Ok(a / b)
            }
        }
        _ => Err("Unknown operation".to_string()),
    }
}

// Command returning structured data
#[tauri::command]
fn get_system_info() -> SystemInfo {
    SystemInfo {
        os: std::env::consts::OS.to_string(),
        arch: std::env::consts::ARCH.to_string(),
        version: env!("CARGO_PKG_VERSION").to_string(),
    }
}

// Async command example
#[tauri::command]
async fn async_operation(duration: u64) -> String {
    tokio::time::sleep(tokio::time::Duration::from_secs(duration)).await;
    format!("Async operation completed after {} seconds", duration)
}

// Command with state
#[tauri::command]
fn get_counter(state: tauri::State<Counter>) -> i32 {
    *state.count.lock().unwrap()
}

#[tauri::command]
fn increment_counter(state: tauri::State<Counter>) -> i32 {
    let mut count = state.count.lock().unwrap();
    *count += 1;
    *count
}

// State struct
struct Counter {
    count: std::sync::Mutex<i32>,
}

fn main() {
    tauri::Builder::default()
        .manage(Counter {
            count: std::sync::Mutex::new(0),
        })
        .invoke_handler(tauri::generate_handler![
            greet,
            calculate,
            get_system_info,
            async_operation,
            get_counter,
            increment_counter
        ])
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
