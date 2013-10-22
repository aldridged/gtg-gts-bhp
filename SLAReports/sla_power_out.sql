select timestamp,
substring(rawData,locate(':',rawData)+1,(locate(' ',rawData)-(locate(':',rawData))-1)) as Latency,
substring(rawData,locate(':',rawData,10)+1,(locate('%',rawData)-locate(':',rawData,10)-1)) as PacketLoss,
substring(rawData,locate(':',rawData,35)+1,(locate(' ',rawData,35)-locate(':',rawData,35)-1)) as PublicIP,
substring(rawData,locate(':',rawData,40)+1) as Uptime
from EventData 
where accountID='gtg' and deviceID='bbwbhp1' and rawData is not null order by timestamp desc limit 144;