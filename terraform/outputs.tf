output "ec2_public_ip" {
  value = aws_instance.smart_scrap_server.public_ip
}