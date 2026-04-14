import { ChatMessage } from '../types';
export declare function constructChatWebsocketUrl(url: string, executionId: string, sessionId: string, isPublic: boolean): string;
export declare function parseBotChatMessageContent(message: string): ChatMessage;
export declare function shouldBlockUserInput(message: ChatMessage): boolean;
