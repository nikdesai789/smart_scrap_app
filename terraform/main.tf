# 1. Create a Security Group to allow web traffic to our server
resource "aws_security_group" "smart_scrap_sg" {
  name        = "smart-scrap-security-group"
  description = "Allow web and SSH traffic"

  # Allow HTTP web traffic
  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  # Allow Docker container mapping traffic (port 8080)
  ingress {
    from_port   = 8080
    to_port     = 8080
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  # Allow SSH access for administration
  ingress {
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"] # In production, restrict this to your IP
  }

  # Allow all outbound traffic (so the server can download updates/Docker images)
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}


# 2. Create the EC2 Virtual Server
resource "aws_instance" "smart_scrap_server" {
  ami           = "ami-0c7217cdde317cfec" 
  instance_type = "t3.micro"             
  key_name      = "smart-scrap-key"      # <-- ADD THIS LINE

  vpc_security_group_ids = [aws_security_group.smart_scrap_sg.id]

  tags = {
    Name = "SmartScrap-ProductionServer"
  }
}

# 3. Create the S3 Bucket for your scraper storage
resource "aws_s3_bucket" "smart_scrap_storage" {
  bucket = "nikdesai-smart-scrap-storage-unique-bucket" # S3 bucket names must be globally unique

  tags = {
    Name        = "SmartScrapStorage"
    Environment = "Production"
  }
}