output "ec2_public_ip" {
  description = "The public IP address of our production server"
  value       = aws_instance.smart_scrap_server.public_ip
}