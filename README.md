# Smart Mirror Project

## Introduction

This project focuses on handling large datasets, analyzing user behavior algorithms, and addressing concurrency issues in database operations.

## Challenges and Solutions

1. **Handling Large Data**: 
   - Solution: Data is first stored in RabbitMQ, then a worker processes and stores it in the database.

2. **User Behavior Analysis Algorithm**: 
   - Solution: Real-time data is fetched from RabbitMQ, analyzed, and stored for user behavior analysis.

3. **Concurrency Issue in Database**: 
   - Solution: An index on `user_id` and `start_time` was added to improve speed and handle concurrency issues at the database layer.

## Implementation

### Data Storage in RabbitMQ
All data is initially stored in RabbitMQ. This ensures that even if data is lost, it can be re-sent without any loss.

### Worker for Data Storage in Database
A worker written using the Phalcon framework moves data from RabbitMQ to the database. For optimal performance, it is recommended to implement the worker as a Super Worker.

### Indexing to Prevent Concurrency Issues
To enhance performance and avoid concurrency issues during data insertion, a combined index on `user_id` and `start_time` is used. This improves read speed and resolves concurrency problems.

## Setup and Run

### Prerequisites
1. **Install Docker and Docker Compose**: First, make sure you have Docker and Docker Compose installed.

### Running Tests
To run the tests, execute the following command:

```bash
composer run-script phpTest

