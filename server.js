import express from "express";
import dotenv from "dotenv";
import path from "path";
import { fileURLToPath } from "url";
import { GoogleGenAI } from "@google/genai";

dotenv.config();

const app = express();
const port = 3000;

const ai = new GoogleGenAI({
  apiKey: process.env.GEMINI_API_KEY,
});

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

app.get("/", (req, res) => {
  res.sendFile(path.join(__dirname, "testttt.html"));
});

app.get("/generate-question", async (req, res) => {
  try {
    const response = await ai.models.generateContent({
      model: "gemini-3-flash",
      contents:
        "Generate one easy beginner HTML quiz question. Return only the question text.",
    });

    res.json({
      question: response.text || "No question returned.",
    });
  } catch (error) {
    console.error("GEMINI ERROR:", error);
    res.status(500).json({
      question: "Failed to generate question.",
    });
  }
});

app.listen(port, () => {
  console.log(`Server running at http://localhost:${port}`);
});