require('dotenv').config({ override: true, debug: false });

const solanaWeb3 = require('@solana/web3.js');
const crypto = require('crypto');

const PLATFORM_KEY = process.env.PLATFORM_KEY;

function encryptPrivateKey(privateKey) {
  const key = crypto.createHash('sha256').update(PLATFORM_KEY).digest();
  const iv = crypto.randomBytes(16);
  const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
  const encrypted = Buffer.concat([cipher.update(privateKey, 'utf8'), cipher.final()]);
  return iv.toString('hex') + ':' + encrypted.toString('hex');
}

// 生成 Keypair
const keypair = solanaWeb3.Keypair.generate();
const publicKey = keypair.publicKey.toBase58();
const privateKey = Buffer.from(keypair.secretKey).toString('base64');
const encryptedPrivateKey = encryptPrivateKey(privateKey);

console.log(JSON.stringify({
    address: publicKey,
    private_key: encryptedPrivateKey
}));
