#!/bin/sh

######## Configuration for forwarding ###########

sysctl -w net.ipv4.conf.all.forwarding=1
sysctl -w net.ipv4.conf.all.rp_filter=0
sysctl -w net.ipv4.conf.default.rp_filter=0

for eth in `ifconfig -a | grep -o -e "eth[0-9]\+" | uniq`
do
	sysctl -w net.ipv4.conf.${eth}.rp_filter=0	
done

for wlan in `ifconfig -a | grep -o -e "wlan[0-9]\+" | uniq`
do
	sysctl -w net.ipv4.conf.${wlan}.rp_filter=0	
done

#echo "1" > /proc/sys/net/ipv4/ip_forward
#echo "1" | sudo tee /proc/sys/net/ipv4/ip_forward

########## Configuration for Squid3 #############
 
################# Main options ##################

 trusthost='0.0.0.0/0'
 internal_ip='192.168.1.0/24'
 #internal_ip='192.168.1.1'
 
 my_internet_ip='192.168.0.6'
 my_internal_ip='192.168.1.3'
 
 proxy_port='3128'

############## Deafult Rule ##############
iptables -P INPUT DROP
iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

iptables -P OUTPUT ACCEPT

iptables -P FORWARD DROP
iptables -A FORWARD -i eth0 -o wlan0 -s $internal_ip -j ACCEPT
iptables -A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT

######### loopback #########
iptables -A INPUT -i lo -j ACCEPT
iptables -A OUTPUT -o lo -j ACCEPT
####################### ICMP trusthost->myhost #######################
iptables -A INPUT -p icmp --icmp-type echo-request -s $trusthost -d $my_internal_ip -j ACCEPT
iptables -A OUTPUT -p icmp --icmp-type echo-reply  -s $my_internal_ip -d $trusthost -j ACCEPT
####################### ICMP myhost->trusthost #######################
iptables -A OUTPUT -p icmp --icmp-type echo-request -s $my_internal_ip -d $trusthost -j ACCEPT
iptables -A INPUT -p icmp --icmp-type echo-reply -s $trusthost -d $my_internal_ip -j ACCEPT

######################### Proxy trusthost-> myhost #########################
iptables -A INPUT -p tcp -m state --state NEW,ESTABLISHED,RELATED -s $internal_ip -d $my_internal_ip --dport $proxy_port -j ACCEPT
iptables -A OUTPUT -p tcp -s $my_internal_ip --sport $proxy_port -d $internal_ip -j ACCEPT

################# SNAT(masquerade) #################
iptables -t nat -A POSTROUTING -o wlan0 -s $internal_ip -j MASQUERADE

#################### Transparently proxy ####################
iptables -t nat -A PREROUTING -i eth0 -p tcp --dport 80 -j REDIRECT --to-port $proxy_port

################## Sub options #################

### Outgoing packet should be real internet Address ###
#iptables -A OUTPUT -o wlan0 -d 10.0.0.0/8 -j DROP
#iptables -A OUTPUT -o wlan0 -d 176.16.0.0/12 -j DROP
#iptables -A OUTPUT -o wlan0 -d 192.168.0.0/16 -j DROP
#iptables -A OUTPUT -o wlan0 -d 127.0.0.0/8 -j DROP

######### logging #########
iptables -N LOGGING
iptables -A LOGGING -j LOG --log-level warning --log-prefix "DROP:" -m limit
iptables -A LOGGING -j DROP
iptables -A INPUT -j LOGGING
iptables -A FORWARD -j LOGGING
