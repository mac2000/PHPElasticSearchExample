Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/trusty64"
  config.vm.network "forwarded_port", guest: 80, host: 8080
  config.vm.network "forwarded_port", guest: 3306, host: 3307
  config.vm.network "forwarded_port", guest: 9200, host: 9200
  #config.vm.network "private_network", ip: "192.168.33.10"
  #config.vm.network "public_network"
  config.vm.provision "shell", path: "Provision.sh"
  config.vm.provider "virtualbox" do |vb|
    vb.memory = 512
  end
end
