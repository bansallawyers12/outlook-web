#!/usr/bin/env python3

import boto3
import os
import sys
from pathlib import Path

def test_s3_connection():
    """Test S3 connection and upload functionality"""
    
    # Get AWS credentials from environment
    aws_access_key = os.getenv('AWS_ACCESS_KEY_ID')
    aws_secret_key = os.getenv('AWS_SECRET_ACCESS_KEY')
    aws_region = os.getenv('AWS_DEFAULT_REGION')
    aws_bucket = os.getenv('AWS_BUCKET')
    
    if not all([aws_access_key, aws_secret_key, aws_region, aws_bucket]):
        print("❌ Missing AWS credentials in environment variables")
        print("Required: AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET")
        return False
    
    try:
        print("Testing S3 connection...")
        
        # Create S3 client
        s3_client = boto3.client(
            's3',
            aws_access_key_id=aws_access_key,
            aws_secret_access_key=aws_secret_key,
            region_name=aws_region
        )
        
        # Test bucket access
        s3_client.head_bucket(Bucket=aws_bucket)
        print("✓ S3 bucket access successful!")
        
        # Test file upload
        test_content = "This is a test file for S3 upload from Python"
        test_file_path = "test/python-test.txt"
        
        # Create temporary file
        temp_file = Path("temp_test.txt")
        temp_file.write_text(test_content)
        
        # Upload to S3
        s3_client.upload_file(str(temp_file), aws_bucket, test_file_path)
        print("✓ File upload successful!")
        
        # Test file download
        downloaded_content = s3_client.get_object(Bucket=aws_bucket, Key=test_file_path)
        if downloaded_content['Body'].read().decode() == test_content:
            print("✓ File download successful!")
        else:
            print("❌ File download failed - content mismatch")
            return False
        
        # Clean up
        s3_client.delete_object(Bucket=aws_bucket, Key=test_file_path)
        temp_file.unlink()
        print("✓ Test file cleaned up")
        
        print("\n🎉 All Python S3 tests passed!")
        return True
        
    except Exception as e:
        print(f"❌ S3 test failed: {e}")
        return False

if __name__ == "__main__":
    success = test_s3_connection()
    sys.exit(0 if success else 1)
